<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PersonaService
{
    protected string $apiKey;
    protected string $apiUrl;
    protected string $templateId;

    public function __construct()
    {
        $this->apiKey = config('persona.api_key');
        $this->apiUrl = config('persona.api_url');
        $this->templateId = config('persona.template_id');
    }

    /**
     * Create an inquiry for identity verification
     */
    public function createInquiry(array $data = []): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Persona-Version' => '2023-01-05',
            ])->post($this->apiUrl . '/inquiries', [
                'data' => [
                    'attributes' => [
                        'inquiry-template-id' => $this->templateId,
                        'reference-id' => $data['reference_id'] ?? null,
                        'note' => $data['note'] ?? null,
                    ],
                ],
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            Log::error('Persona API Error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to create inquiry',
                'details' => $response->json(),
            ];
        } catch (\Exception $e) {
            Log::error('Persona Service Exception', [
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Retrieve an inquiry by ID
     */
    public function getInquiry(string $inquiryId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Persona-Version' => '2023-01-05',
            ])->get($this->apiUrl . '/inquiries/' . $inquiryId);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to retrieve inquiry',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate embedded client token for frontend
     */
    public function generateClientToken(string $inquiryId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl . '/inquiry-sessions', [
                'data' => [
                    'attributes' => [
                        'inquiry-id' => $inquiryId,
                    ],
                ],
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to generate client token',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
