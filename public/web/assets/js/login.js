
document.addEventListener('DOMContentLoaded', function() {
    // Security tracking
    let failedAttempts = 0;
    const maxFailedAttempts = 3;
    let isLocked = false;
    
    // Valid account credentials
    const validCredentials = [
        { account: "9031547821", pin: "4729" },
        { account: "7910438562", pin: "1938" },
        { account: "6524913087", pin: "8271" },
        { account: "8304921765", pin: "5604" },
        { account: "4918672053", pin: "7382" },
        { account: "2103958476", pin: "4950" },
        { account: "6483925107", pin: "6421" },
        { account: "3741892056", pin: "8063" },
        { account: "8592764310", pin: "1537" },
        { account: "7324109685", pin: "9842" },
        { account: "5013298467", pin: "2105" },
        { account: "6841027395", pin: "3681" },
        { account: "7493856102", pin: "5514" },
        { account: "9365748201", pin: "7269" },
        { account: "8146279530", pin: "4098" },
        { account: "9250384716", pin: "1896" },
        { account: "5701948236", pin: "3402" },
        { account: "6839247501", pin: "2157" },
        { account: "4091753286", pin: "6908" },
        { account: "8391526074", pin: "5216" },
        { account: "7421963058", pin: "8103" },
        { account: "9184672305", pin: "4791" },
        { account: "6408257139", pin: "1320" },
        { account: "3957216804", pin: "5786" },
        { account: "2304785916", pin: "3647" },
        { account: "7812054936", pin: "2473" },
        { account: "4569187032", pin: "9801" },
        { account: "8640319572", pin: "6349" },
        { account: "7023481596", pin: "1024" },
        { account: "5940137862", pin: "2917" },
        { account: "3105782496", pin: "7532" },
        { account: "8750246931", pin: "8614" },
        { account: "6034928571", pin: "1907" },
        { account: "4701835926", pin: "6428" },
        { account: "5928371406", pin: "4215" },
        { account: "7109548263", pin: "3708" },
        { account: "8903451276", pin: "1563" },
        { account: "3561940827", pin: "5309" },
        { account: "2198034567", pin: "6075" },
        { account: "6543981207", pin: "3910" },
        { account: "8039476215", pin: "1442" },
        { account: "4762183095", pin: "7094" },
        { account: "5901832476", pin: "8060" },
        { account: "6274183059", pin: "4726" },
        { account: "3489527601", pin: "2138" },
        { account: "7190386524", pin: "3921" },
        { account: "9038176245", pin: "5647" },
        { account: "5618032947", pin: "7859" },
        { account: "3892054716", pin: "1423" },
        { account: "7481920635", pin: "6370" }
    ];

    // PIN visibility toggle
    const togglePin = document.getElementById('togglePin');
    const pinInput = document.getElementById('pin');
    
    if (togglePin && pinInput) {
        togglePin.addEventListener('click', function() {
            const type = pinInput.getAttribute('type') === 'password' ? 'text' : 'password';
            pinInput.setAttribute('type', type);
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });
    }
    
    // Validate credentials function
    function validateCredentials(accountNumber, pin) {
        return validCredentials.some(cred => 
            cred.account === accountNumber && cred.pin === pin
        );
    }
    
    // Show error message
    function showError(message) {
        const loginBtn = document.getElementById('loginBtn');
        if (loginBtn) {
            const originalText = loginBtn.textContent;
            const originalBg = loginBtn.style.backgroundColor;

            // Show error in button
            loginBtn.textContent = message;
            loginBtn.style.backgroundColor = '#ef4444';
            loginBtn.style.animation = 'shake 0.5s ease-in-out';

            setTimeout(() => {
                loginBtn.style.animation = '';
            }, 500);

            // Reset button after 3 seconds
            setTimeout(() => {
                loginBtn.textContent = 'Secure Login';
                loginBtn.style.backgroundColor = originalBg;
            }, 3000);
        }
    }
    
    // Add shake animation CSS
    const style = document.createElement('style');
    style.textContent = `
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
    `;
    document.head.appendChild(style);
    
    // Login button animation and redirect
    const loginBtn = document.getElementById('loginBtn');
    const accountNumberInput = document.getElementById('accountNumber');
    
    if (loginBtn && accountNumberInput && pinInput) {
        loginBtn.addEventListener('click', function() {
            // Check if account is locked
            if (isLocked) {
                showError('Account temporarily locked. Please try again later.');
                return;
            }
            
            const accountNumber = accountNumberInput.value.trim();
            const pin = pinInput.value.trim();
            
            // Validate input fields
            if (!accountNumber || !pin) {
                if (!accountNumber) {
                    accountNumberInput.style.borderColor = '#ef4444';
                    accountNumberInput.focus();
                }
                if (!pin) {
                    pinInput.style.borderColor = '#ef4444';
                    if (accountNumber) pinInput.focus();
                }
                showError('Please fill in all required fields');
                return;
            }
            
            // Validate account number format (10 digits)
            if (accountNumber.length !== 10 || !/^\d{10}$/.test(accountNumber)) {
                accountNumberInput.style.borderColor = '#ef4444';
                accountNumberInput.focus();
                showError('Account number must be 10 digits');
                return;
            }
            
            // Validate PIN format (4 digits)
            if (pin.length !== 4 || !/^\d{4}$/.test(pin)) {
                pinInput.style.borderColor = '#ef4444';
                pinInput.focus();
                showError('PIN must be 4 digits');
                return;
            }
            
            // Validate credentials
            if (!validateCredentials(accountNumber, pin)) {
                failedAttempts++;
                accountNumberInput.style.borderColor = '#ef4444';
                pinInput.style.borderColor = '#ef4444';
                
                if (failedAttempts >= maxFailedAttempts) {
                    isLocked = true;
                    showError('Too many failed attempts. Account locked for 5 minutes.');
                    loginBtn.disabled = true;
                    loginBtn.textContent = 'Account Locked';
                    loginBtn.style.backgroundColor = '#6b7280';
                    
                    // Unlock after 5 minutes
                    setTimeout(() => {
                        isLocked = false;
                        failedAttempts = 0;
                        loginBtn.disabled = false;
                        loginBtn.textContent = 'Secure Login';
                        loginBtn.style.backgroundColor = '';
                    }, 300000); // 5 minutes
                } else {
                    showError(`Invalid credentials. ${maxFailedAttempts - failedAttempts} attempts remaining.`);
                }
                return;
            }
            
            // Reset failed attempts on successful login
            failedAttempts = 0;
            
            // Reset border colors
            accountNumberInput.style.borderColor = '';
            pinInput.style.borderColor = '';
            
            this.classList.add('loading');

            // Simulate security check
            const securityChecks = [
                "Verifying credentials...",
                "Authenticating session...",
                "Establishing secure connection...",
                "Preparing investor dashboard...",
                "Loading portfolio data...",
                "Initializing secure environment..."
            ];

            let checkIndex = 0;

            const securityInterval = setInterval(() => {
                if (checkIndex < securityChecks.length) {
                    // Show security check message in button
                    this.textContent = securityChecks[checkIndex];
                    checkIndex++;
                } else {
                    clearInterval(securityInterval);

                    setTimeout(() => {
                        this.classList.remove('loading');
                        this.textContent = 'Access Granted ✓';
                        this.style.backgroundColor = '#10b981';
                        this.style.color = 'white';

                        setTimeout(() => {
                            // Store login success in sessionStorage for dashboard authentication
                            sessionStorage.setItem('wernLoggedIn', 'true');
                            sessionStorage.setItem('wernAccountNumber', accountNumber);
                            sessionStorage.setItem('wernLoginTime', new Date().toISOString());
                            sessionStorage.setItem('wernUserType', 'investor');

                            // Add smooth transition effect
                            document.body.style.transition = 'opacity 0.5s ease-out';
                            document.body.style.opacity = '0.8';

                            // Redirect to investor command center
                            window.location.href = "https://www.projectliberte.io/wern-investor-dashboard/market-opportunity.html";
                        }, 1500);
                    }, 500);
                }
            }, 500);
        });
        
        // Auto-focus on account number field
        setTimeout(() => {
            accountNumberInput.focus();
        }, 500);
        
        // Input field animations and validation
        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.style.borderColor = '';
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
            });
            
            // Only allow numbers for account number and PIN
            if (input.id === 'accountNumber' || input.id === 'pin') {
                input.addEventListener('input', function() {
                    this.value = this.value.replace(/[^0-9]/g, '');
                });
            }
        });
        
        // Simulate real-time security monitoring
        setInterval(() => {
            const securityBadges = document.querySelectorAll('.security-badge');
            if (securityBadges.length > 0) {
                const randomBadge = securityBadges[Math.floor(Math.random() * securityBadges.length)];
                
                randomBadge.style.transform = 'translateY(-5px)';
                setTimeout(() => {
                    randomBadge.style.transform = 'translateY(0)';
                }, 300);
            }
        }, 5000);
    }
    
    // --- Real Timezone + Real Time Calculations ---
    const locations = [
        { name: 'New York, USA', timezone: 'America/New_York' },
        { name: 'London, UK', timezone: 'Europe/London' },
        { name: 'Tokyo, Japan', timezone: 'Asia/Tokyo' },
        { name: 'Sydney, Australia', timezone: 'Australia/Sydney' },
        { name: 'Hong Kong, China', timezone: 'Asia/Hong_Kong' },
        { name: 'Singapore', timezone: 'Asia/Singapore' },
        { name: 'Paris, France', timezone: 'Europe/Paris' },
        { name: 'Berlin, Germany', timezone: 'Europe/Berlin' },
        { name: 'Toronto, Canada', timezone: 'America/Toronto' },
        { name: 'Mumbai, India', timezone: 'Asia/Kolkata' },
        { name: 'Seoul, South Korea', timezone: 'Asia/Seoul' },
        { name: 'SÃ£o Paulo, Brazil', timezone: 'America/Sao_Paulo' },
        { name: 'Amsterdam, Netherlands', timezone: 'Europe/Amsterdam' },
        { name: 'Stockholm, Sweden', timezone: 'Europe/Stockholm' },
        { name: 'Vienna, Austria', timezone: 'Europe/Vienna' },
        { name: 'Zurich, Switzerland', timezone: 'Europe/Zurich' },
        { name: 'Frankfurt, Germany', timezone: 'Europe/Berlin' },
        { name: 'Dubai, UAE', timezone: 'Asia/Dubai' },
        { name: 'San Francisco, USA', timezone: 'America/Los_Angeles' },
        { name: 'Moscow, Russia', timezone: 'Europe/Moscow' },
        { name: 'Shanghai, China', timezone: 'Asia/Shanghai' },
        { name: 'Johannesburg, South Africa', timezone: 'Africa/Johannesburg' },
        { name: 'Mexico City, Mexico', timezone: 'America/Mexico_City' },
        { name: 'Chicago, USA', timezone: 'America/Chicago' },
        { name: 'Taipei, Taiwan', timezone: 'Asia/Taipei' }
    ];

    const actions = ['Last login', 'Accessed', 'New login'];

    // Store fake "login times" for each location
    let locationLoginTimes = {};

    function getRealLocalTime(location) {
        const now = new Date();
        const options = {
            timeZone: location.timezone,
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        };
        return now.toLocaleTimeString('en-US', options);
    }

    function getRealTimeAgo(location) {
        const now = new Date();
        
        // If no previous login time stored, create a fake one (1-6 hours ago)
        if (!locationLoginTimes[location.name]) {
            const hoursAgo = Math.floor(Math.random() * 6) + 1; // 1-6 hours ago
            const fakeLoginTime = new Date(now.getTime() - (hoursAgo * 60 * 60 * 1000));
            locationLoginTimes[location.name] = fakeLoginTime;
        }
        
        const loginTime = locationLoginTimes[location.name];
        const diffMs = now - loginTime;
        const diffMinutes = Math.floor(diffMs / (1000 * 60));
        
        if (diffMinutes < 1) return 'just now';
        if (diffMinutes < 60) return `${diffMinutes} minute${diffMinutes > 1 ? 's' : ''} ago`;
        
        const diffHours = Math.floor(diffMinutes / 60);
        const remainingMinutes = diffMinutes % 60;
        
        if (remainingMinutes === 0) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
        return `${diffHours} hour${diffHours > 1 ? 's' : ''}, ${remainingMinutes} minute${remainingMinutes > 1 ? 's' : ''} ago`;
    }

    function createRealTimeEvent() {
        const location = locations[Math.floor(Math.random() * locations.length)];
        const action = actions[Math.floor(Math.random() * actions.length)];
        const localTime = getRealLocalTime(location);
        const timeAgo = getRealTimeAgo(location);
        
        return {
            action,
            location: location.name,
            localTime,
            timeAgo
        };
    }

    function updateRealTimeFeed() {
        const event = createRealTimeEvent();
        const lastLoginEl = document.querySelector('.last-login');
        
        if (lastLoginEl) {
            lastLoginEl.innerHTML = `<i class='fas fa-clock'></i> ${event.action}: ${event.timeAgo} from ${event.location} (${event.localTime})`;
        }
    }

    // Update every 5 seconds with real times
    setInterval(updateRealTimeFeed, 5000);

    // Initialize with one message
    updateRealTimeFeed();
    
    // NDA Request Functionality
    const ndaEmailInput = document.getElementById('ndaEmail');
    const ndaRequestBtn = document.getElementById('ndaRequestBtn');
    const ndaStatus = document.getElementById('ndaStatus');
    
    if (ndaRequestBtn && ndaEmailInput && ndaStatus) {
        ndaRequestBtn.addEventListener('click', function() {
            const email = ndaEmailInput.value.trim();
            
            // Validate email
            if (!email) {
                showNdaStatus('Please enter your email address', 'error');
                ndaEmailInput.focus();
                return;
            }
            
            if (!isValidEmail(email)) {
                showNdaStatus('Please enter a valid email address', 'error');
                ndaEmailInput.focus();
                return;
            }
            
            // Show loading state
            ndaRequestBtn.textContent = 'Processing...';
            ndaRequestBtn.disabled = true;
            ndaRequestBtn.style.opacity = '0.7';
            
            // Send NDA request to server
            sendNdaRequest(email);
        });
        
        // Email validation on input (convert to lowercase)
        ndaEmailInput.addEventListener('input', function() {
            // Convert to lowercase for case-insensitive handling
            this.value = this.value.toLowerCase();
            
            if (ndaStatus.style.display !== 'none') {
                ndaStatus.style.display = 'none';
            }
        });
        
        // Also convert to lowercase on blur (when user leaves the field)
        ndaEmailInput.addEventListener('blur', function() {
            this.value = this.value.toLowerCase();
        });
        
        // Convert to lowercase on keyup as well (handles auto-correct)
        ndaEmailInput.addEventListener('keyup', function() {
            this.value = this.value.toLowerCase();
        });
    }
    
    // Email validation function (case-insensitive)
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/i;
        return emailRegex.test(email);
    }
    
    // Send NDA request to server
    function sendNdaRequest(email) {
        fetch('nda_request.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `email=${encodeURIComponent(email)}`
        })
        .then(response => response.json())
        .then(data => {
            // Reset button
            ndaRequestBtn.textContent = 'Request NDA & Access';
            ndaRequestBtn.disabled = false;
            ndaRequestBtn.style.opacity = '1';
            
            if (data.status === 'success') {
                // Store NDA request in sessionStorage
                sessionStorage.setItem('wernNdaRequested', 'true');
                sessionStorage.setItem('wernNdaEmail', email);
                sessionStorage.setItem('wernNdaRequestTime', new Date().toISOString());
                
                // Show success message
                showNdaStatus('âœ… NDA request submitted successfully! You will receive the NDA and access details via email shortly.', 'success');
                
                // Clear email input
                ndaEmailInput.value = '';
                
                // Show additional info
                setTimeout(() => {
                    showNdaStatus('ðŸ“§ Check your email for the NDA document and investor portal access instructions.', 'info');
                }, 2000);
            } else {
                showNdaStatus('âŒ Error: ' + data.message, 'error');
            }
        })
        .catch(error => {
            // Reset button
            ndaRequestBtn.textContent = 'Request NDA & Access';
            ndaRequestBtn.disabled = false;
            ndaRequestBtn.style.opacity = '1';
            
            showNdaStatus('âŒ Network error. Please try again.', 'error');
        });
    }
    
    // Show NDA status message
    function showNdaStatus(message, type) {
        ndaStatus.style.display = 'block';
        ndaStatus.textContent = message;
        
        // Reset styles
        ndaStatus.style.color = '';
        ndaStatus.style.backgroundColor = '';
        ndaStatus.style.padding = '';
        ndaStatus.style.borderRadius = '';
        
        switch(type) {
            case 'success':
                ndaStatus.style.color = '#10b981';
                ndaStatus.style.backgroundColor = 'rgba(16, 185, 129, 0.1)';
                ndaStatus.style.padding = '10px 15px';
                ndaStatus.style.borderRadius = '8px';
                break;
            case 'error':
                ndaStatus.style.color = '#ef4444';
                ndaStatus.style.backgroundColor = 'rgba(239, 68, 68, 0.1)';
                ndaStatus.style.padding = '10px 15px';
                ndaStatus.style.borderRadius = '8px';
                break;
            case 'info':
                ndaStatus.style.color = '#3a86ff';
                ndaStatus.style.backgroundColor = 'rgba(58, 134, 255, 0.1)';
                ndaStatus.style.padding = '10px 15px';
                ndaStatus.style.borderRadius = '8px';
                break;
        }
        
        // Auto-hide after 8 seconds
        setTimeout(() => {
            ndaStatus.style.display = 'none';
        }, 8000);
    }
});