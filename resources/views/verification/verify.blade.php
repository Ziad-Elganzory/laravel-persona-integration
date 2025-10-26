<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Identity Verification</title>
    <script src="https://cdn.withpersona.com/dist/persona-v5.3.1.js"></script>
    @guest
        <script>window.location.href = '/login';</script>
    @endguest
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 48px;
            max-width: 500px;
            width: 100%;
        }

        h1 {
            color: #1a202c;
            font-size: 28px;
            margin-bottom: 12px;
            text-align: center;
        }

        p {
            color: #718096;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 32px;
            text-align: center;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 24px;
        }

        .status-unverified {
            background: #fef3c7;
            color: #92400e;
        }

        .status-pending {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-verified {
            background: #d1fae5;
            color: #065f46;
        }

        .status-rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        button {
            width: 100%;
            background: #667eea;
            color: white;
            border: none;
            padding: 16px 32px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(102, 126, 234, 0.3);
        }

        button:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(102, 126, 234, 0.4);
        }

        button:disabled {
            background: #cbd5e0;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .loading {
            display: none;
            text-align: center;
            margin-top: 20px;
        }

        .loading.active {
            display: block;
        }

        .spinner {
            border: 3px solid #f3f4f6;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .error {
            background: #fee2e2;
            border-left: 4px solid #dc2626;
            color: #991b1b;
            padding: 16px;
            border-radius: 8px;
            margin-top: 20px;
            display: none;
        }

        .error.active {
            display: block;
        }

        .success-icon {
            font-size: 64px;
            text-align: center;
            margin-bottom: 20px;
        }

        .features {
            margin-top: 32px;
            padding-top: 32px;
            border-top: 1px solid #e5e7eb;
        }

        .feature {
            display: flex;
            align-items: start;
            margin-bottom: 16px;
        }

        .feature-icon {
            background: #ede9fe;
            color: #667eea;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            flex-shrink: 0;
            font-weight: bold;
        }

        .feature-text {
            font-size: 14px;
            color: #4b5563;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="container">
        <div id="status-section">
            <h1 id="main-title">Identity Verification</h1>
            <p id="main-description">Complete your identity verification to access all features of your account.</p>

            <div id="status-display" style="text-align: center;">
                <span class="status-badge status-unverified">Not Verified</span>
            </div>

            <button id="verify-btn" onclick="startVerification()">
                Start Verification
            </button>

            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p style="margin-top: 12px; color: #718096;">Processing...</p>
            </div>

            <div class="error" id="error"></div>
            
            <div id="status-message" style="margin-top: 20px; padding: 16px; border-radius: 8px; display: none; text-align: center;"></div>
        </div>

        <div class="features">
            <div class="feature">
                <div class="feature-icon">🔒</div>
                <div class="feature-text">
                    <strong>Secure & Private</strong><br>
                    Your information is encrypted and protected
                </div>
            </div>
            <div class="feature">
                <div class="feature-icon">⚡</div>
                <div class="feature-text">
                    <strong>Quick Process</strong><br>
                    Verification takes just a few minutes
                </div>
            </div>
            <div class="feature">
                <div class="feature-icon">✓</div>
                <div class="feature-text">
                    <strong>One Time Only</strong><br>
                    You only need to verify once
                </div>
            </div>
        </div>
    </div>

    <script>
        // Get CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Check status on load
        document.addEventListener('DOMContentLoaded', checkStatus);

        async function checkStatus() {
            try {
                const response = await fetch('/verify/status', {
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    }
                });

                const data = await response.json();
                updateStatusDisplay(data.verification_status || data.status);
            } catch (error) {
                console.error('Error checking status:', error);
            }
        }

        function updateStatusDisplay(status) {
            const statusDisplay = document.getElementById('status-display');
            const verifyBtn = document.getElementById('verify-btn');
            const mainTitle = document.getElementById('main-title');
            const mainDescription = document.getElementById('main-description');
            const statusMessage = document.getElementById('status-message');

            let badgeHtml = '';

            switch(status) {
                case 'verified':
                case 'approved':
                    badgeHtml = '<span class="status-badge status-verified">✓ Verified</span>';
                    verifyBtn.disabled = true;
                    verifyBtn.textContent = 'Already Verified';
                    mainTitle.textContent = '✓ Verification Complete';
                    mainDescription.textContent = 'Your identity has been successfully verified!';
                    statusMessage.style.display = 'block';
                    statusMessage.style.background = '#d1fae5';
                    statusMessage.style.color = '#065f46';
                    statusMessage.innerHTML = '<strong>🎉 Congratulations!</strong><br>You now have full access to all features.';
                    break;
                    
                case 'pending':
                    badgeHtml = '<span class="status-badge status-pending">⏳ In Progress</span>';
                    verifyBtn.disabled = true;
                    verifyBtn.textContent = 'Verification In Progress';
                    statusMessage.style.display = 'block';
                    statusMessage.style.background = '#dbeafe';
                    statusMessage.style.color = '#1e40af';
                    statusMessage.innerHTML = '<strong>Pending Phase</strong><br>You started the verification process. Please complete all steps.';
                    break;
                    
                case 'completed':
                    badgeHtml = '<span class="status-badge status-pending">📋 Awaiting Review</span>';
                    verifyBtn.disabled = true;
                    verifyBtn.textContent = 'Documents Submitted';
                    mainTitle.textContent = 'Documents Submitted';
                    mainDescription.textContent = 'Your verification is complete from your end. We\'re reviewing your information.';
                    statusMessage.style.display = 'block';
                    statusMessage.style.background = '#dbeafe';
                    statusMessage.style.color = '#1e40af';
                    statusMessage.innerHTML = '<strong>Done Phase - Completed</strong><br>You\'ve finished all required steps. Our team will review your submission and you\'ll be notified of the decision soon.<br><br><em style="font-size: 13px;">⏱ This typically takes a few minutes to a few hours.</em>';
                    break;
                    
                case 'needs_review':
                    badgeHtml = '<span class="status-badge status-pending">👤 Manual Review</span>';
                    verifyBtn.disabled = true;
                    verifyBtn.textContent = 'Under Manual Review';
                    mainTitle.textContent = 'Manual Review Required';
                    mainDescription.textContent = 'Your verification requires additional review by our team.';
                    statusMessage.style.display = 'block';
                    statusMessage.style.background = '#fef3c7';
                    statusMessage.style.color = '#92400e';
                    statusMessage.innerHTML = '<strong>Post-Inquiry Phase - Needs Review</strong><br>Our compliance team is manually reviewing your submission. You\'ll be notified once the review is complete.';
                    break;
                    
                case 'rejected':
                case 'declined':
                    badgeHtml = '<span class="status-badge status-rejected">✗ Declined</span>';
                    verifyBtn.disabled = false;
                    verifyBtn.textContent = 'Try Again';
                    mainTitle.textContent = 'Verification Declined';
                    mainDescription.textContent = 'Your verification was not approved. You can try again.';
                    statusMessage.style.display = 'block';
                    statusMessage.style.background = '#fee2e2';
                    statusMessage.style.color = '#991b1b';
                    statusMessage.innerHTML = '<strong>Post-Inquiry Phase - Declined</strong><br>Unfortunately, we couldn\'t verify your identity with the information provided. Please try again with different documents.';
                    break;
                    
                case 'failed':
                    badgeHtml = '<span class="status-badge status-rejected">✗ Failed</span>';
                    verifyBtn.disabled = false;
                    verifyBtn.textContent = 'Restart Verification';
                    mainTitle.textContent = 'Verification Failed';
                    mainDescription.textContent = 'Something went wrong during the verification process.';
                    statusMessage.style.display = 'block';
                    statusMessage.style.background = '#fee2e2';
                    statusMessage.style.color = '#991b1b';
                    statusMessage.innerHTML = '<strong>Done Phase - Failed</strong><br>The verification process encountered an error. Please try again.';
                    break;
                    
                case 'expired':
                    badgeHtml = '<span class="status-badge status-rejected">⏰ Expired</span>';
                    verifyBtn.disabled = false;
                    verifyBtn.textContent = 'Start New Verification';
                    mainTitle.textContent = 'Verification Expired';
                    mainDescription.textContent = 'Your verification session has expired after 24 hours.';
                    statusMessage.style.display = 'block';
                    statusMessage.style.background = '#fee2e2';
                    statusMessage.style.color = '#991b1b';
                    statusMessage.innerHTML = '<strong>Done Phase - Expired</strong><br>The verification link expired. Please start a new verification process.';
                    break;
                    
                case 'created':
                    badgeHtml = '<span class="status-badge status-unverified">Created</span>';
                    verifyBtn.disabled = false;
                    verifyBtn.textContent = 'Start Verification';
                    statusMessage.style.display = 'block';
                    statusMessage.style.background = '#f3f4f6';
                    statusMessage.style.color = '#4b5563';
                    statusMessage.innerHTML = '<strong>Created Phase</strong><br>Your verification inquiry has been created. Click the button above to begin.';
                    break;
                    
                default:
                    badgeHtml = '<span class="status-badge status-unverified">Not Started</span>';
                    verifyBtn.disabled = false;
                    verifyBtn.textContent = 'Start Verification';
                    statusMessage.style.display = 'none';
            }

            statusDisplay.innerHTML = badgeHtml;
        }

        function pollStatus() {
            const interval = setInterval(async () => {
                try {
                    const response = await fetch('/verify/status', {
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        }
                    });
                    const data = await response.json();

                    updateStatusDisplay(data.verification_status || data.status);

                    if (data.verification_status === 'verified' || data.verification_status === 'approved') {
                        clearInterval(interval);
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    }
                } catch (error) {
                    console.error('Error polling status:', error);
                }
            }, 3000);

            // Clear interval after 5 minutes
            setTimeout(() => clearInterval(interval), 300000);
        }

        async function startVerification() {
            const loadingEl = document.getElementById('loading');
            const errorEl = document.getElementById('error');
            const verifyBtn = document.getElementById('verify-btn');

            // Show loading
            loadingEl.classList.add('active');
            errorEl.classList.remove('active');
            verifyBtn.disabled = true;

            try {
                // Create inquiry
                const response = await fetch('/verify/create-inquiry', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to create verification inquiry');
                }

                const data = await response.json();

                // Initialize Persona Client
                const client = new Persona.Client({
                    inquiryId: data.inquiry_id,
                    sessionToken: data.session_token,
                    environment: '{{ config("persona.environment") }}',
                    onReady: () => {
                        console.log('Persona client ready');
                        loadingEl.classList.remove('active');
                    },
                    onLoad: () => {
                        console.log('Persona iframe loaded');
                        loadingEl.classList.remove('active');
                    },
                    onComplete: ({ inquiryId, status, fields }) => {
                        console.log('Verification completed:', { inquiryId, status, fields });
                        loadingEl.classList.add('active');
                        errorEl.classList.remove('active');

                        // Start polling for status updates
                        pollStatus();

                        // Show success message
                        const statusDisplay = document.getElementById('status-display');
                        statusDisplay.innerHTML = '<span class="status-badge status-pending">⏳ Processing...</span>';
                        verifyBtn.disabled = true;
                        verifyBtn.textContent = 'Processing Verification';
                    },
                    onCancel: ({ inquiryId, sessionToken }) => {
                        console.log('Verification cancelled:', inquiryId);
                        loadingEl.classList.remove('active');
                        verifyBtn.disabled = false;
                        errorEl.textContent = 'Verification was cancelled. You can try again anytime.';
                        errorEl.classList.add('active');
                    },
                    onError: (error) => {
                        console.error('Persona error:', error);
                        loadingEl.classList.remove('active');
                        verifyBtn.disabled = false;
                        errorEl.textContent = 'An error occurred during verification. Please try again.';
                        errorEl.classList.add('active');
                    }
                });

                // Open the verification flow
                client.open();

            } catch (error) {
                console.error('Error:', error);
                loadingEl.classList.remove('active');
                verifyBtn.disabled = false;
                errorEl.textContent = error.message || 'Failed to start verification. Please try again.';
                errorEl.classList.add('active');
            }
        }
    </script>
</body>
</html>
