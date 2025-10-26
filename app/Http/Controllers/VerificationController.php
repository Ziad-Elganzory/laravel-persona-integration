<?php

namespace App\Http\Controllers;

use App\Services\PersonaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class VerificationController extends Controller
{
    protected PersonaService $personaService;

    public function __construct(PersonaService $personaService)
    {
        $this->personaService = $personaService;
    }

    /**
     * Show verification page
     */
    public function show()
    {
        return view('verification.verify');
    }

    /**
     * Create a new inquiry and return session token
     */
    public function createInquiry(Request $request)
    {
        $user = Auth::user();

        // Create inquiry with user reference
        $inquiry = $this->personaService->createInquiry([
            'reference_id' => $user->id,
            'note' => 'User verification for ' . $user->email,
        ]);

        if (!$inquiry['success']) {
            return response()->json([
                'error' => 'Failed to create verification inquiry',
            ], 500);
        }

        $inquiryId = $inquiry['data']['data']['id'];

        $session = $this->personaService->generateClientToken($inquiryId);

        if (!$session['success']) {
            return response()->json([
                'error' => 'Failed to generate session token',
            ], 500);
        }

        // Store inquiry ID in user record
        $user->update([
            'persona_inquiry_id' => $inquiryId,
        ]);

        return response()->json([
            'inquiry_id' => $inquiryId,
            'session_token' => $session['data']['data']['id'],
        ]);
    }

    /**
     * Handle webhook from Persona
     */
    public function webhook(Request $request)
    {
        // Log raw request for debugging
        Log::info('Persona Webhook Received - Raw', [
            'headers' => $request->headers->all(),
            'body' => $request->getContent(),
            'all' => $request->all(),
        ]);

        $payload = $request->all();

        Log::info('Persona Webhook Payload', $payload);

        $eventName = $payload['data']['attributes']['name'] ?? null;
        Log::info('Persona Webhook Event Type', ['event_type' => $eventName]);
        $inquiryId = $payload['data']['attributes']['payload']['data']['id'] ?? null;
        Log::info('Persona Webhook Inquiry ID', ['inquiry_id' => $inquiryId]);
        $status = $payload['data']['attributes']['payload']['data']['attributes']['status'] ?? null;

        if (!$inquiryId) {
            return response()->json(['message' => 'No inquiry ID found'], 400);
        }

        // Find user by inquiry ID
        $user = \App\Models\User::where('persona_inquiry_id', $inquiryId)->first();

        if (!$user) {
            Log::warning('User not found for inquiry', ['inquiry_id' => $inquiryId]);
            return response()->json(['message' => 'User not found'], 404);
        }

        switch ($eventName) {
            case 'inquiry.created':
                Log::info('Inquiry created', ['inquiry_id' => $inquiryId]);

                $user->update([
                    'verification_status' => 'created',
                ]);
                break;

            case 'inquiry.started':
                Log::info('User started verification', ['user_id' => $user->id]);

                $user->update([
                    'verification_status' => 'pending',
                ]);
                break;

            case 'inquiry.completed':
                Log::info('User completed verification flow', ['user_id' => $user->id]);

                $user->update([
                    'verification_status' => 'completed',
                ]);
                break;

            case 'inquiry.failed':
                Log::info('User failed verification flow', ['user_id' => $user->id]);

                $user->update([
                    'verification_status' => 'failed',
                ]);
                break;

            case 'inquiry.expired':
                Log::info('Inquiry expired', [
                    'user_id' => $user->id,
                    'expired_at' => $payload['data']['attributes']['payload']['data']['attributes']['expired-at'] ?? now(),
                ]);

                $user->update([
                    'verification_status' => 'expired',
                ]);
                break;
            case 'inquiry.approved':
                Log::info('✓ Inquiry APPROVED', ['user_id' => $user->id]);

                $user->update([
                    'verification_status' => 'verified', // or 'approved'
                    'verified_at' => now(),
                ]);
                break;

            case 'inquiry.declined':
                Log::info('✗ Inquiry DECLINED', ['user_id' => $user->id]);

                $user->update([
                    'verification_status' => 'rejected',
                ]);
                break;

            case 'inquiry.needs_review':
                Log::info('⏳ Inquiry needs manual review', ['user_id' => $user->id]);

                $user->update([
                    'verification_status' => 'needs_review',
                ]);
                break;
            default:
                Log::warning('Unhandled inquiry event', [
                    'event_name' => $eventName,
                    'status' => $status,
                ]);
        }

        return response()->json(['message' => 'Webhook processed successfully']);
    }

    /**
     * Check verification status
     */
    public function status()
    {
        $user = Auth::user();

        if (!$user->persona_inquiry_id) {
            return response()->json([
                'status' => 'not_started',
            ]);
        }

        $inquiry = $this->personaService->getInquiry($user->persona_inquiry_id);

        if ($inquiry['success']) {
            $status = $inquiry['data']['data']['attributes']['status'] ?? 'pending';

            return response()->json([
                'status' => $status,
                'verification_status' => $user->verification_status,
            ]);
        }

        return response()->json([
            'status' => 'error',
            'error' => 'Failed to retrieve status',
        ], 500);
    }
}
