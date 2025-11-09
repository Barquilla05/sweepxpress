<?php 
// -------------------------------------------------------------------
// AGGRESSIVE AJAX HANDLER (KEPT FOR CRITICAL CLEANUP)
// -------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_delete'])) {
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    require_once __DIR__ . '/config.php';
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'The delete function has been disabled.'];
    echo json_encode($response); 
    exit;
}
// -------------------------------------------------------------------

// Normal page loading continues below
require_once __DIR__ . '/includes/header.php'; 
require_once __DIR__ . '/config.php'; 

// Initialize user variables
$user_email = null;
$user_name = null;

// Determine login status and user details
$is_logged_in = function_exists('is_logged_in') && is_logged_in();

if ($is_logged_in) {
    $user_email = $_SESSION['user']['email'];
    $user_name = $_SESSION['user']['name'] ?? '';
}

// Helper string for disabling fields if logged in
$disabled_if_logged_in = $is_logged_in ? 'disabled' : '';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h1 class="h3 mb-4 text-center">📞 Contact Us</h1>
            <p class="text-muted text-center mb-4">
                Have questions or feedback? Just drop your message below — we’d love to hear from you!
            </p>

            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <form method="POST" id="contactForm" action="#"> 
                        
                        <input type="hidden" name="_formspree" value="movppngk">
                        
                        <?php if (!$is_logged_in): ?>
                            <div class='alert alert-info mt-2'>
                                👋 You are sending this message as a **Guest**. Please fill in your name and email to receive a reply.
                            </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="name_input" class="form-label">Your Name</label>
                            <input 
                                type="text"
                                class="form-control" 
                                id="name_input" 
                                name="name" value="<?= htmlspecialchars($user_name ?? '') ?>"
                                required
                                <?= $disabled_if_logged_in ?>
                            />
                            </div>
                        
                        <div class="mb-3">
                            <label for="email_input" class="form-label">Your Email</label>
                            <input 
                                type="email"
                                class="form-control" 
                                id="email_input" 
                                name="_replyto" value="<?= htmlspecialchars($user_email ?? '') ?>"
                                required
                                <?= $disabled_if_logged_in ?>
                            />
                            </div>
                        
                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea 
                                class="form-control" 
                                id="message" 
                                name="message" 
                                rows="5" 
                                placeholder="Write your message here..." 
                                required
                            ></textarea>
                            </div>
                        
                        <div class="d-flex gap-2 align-items-center">
                            <button 
                                type="submit" 
                                class="btn btn-primary flex-grow-1"
                                id="submitButton"
                            >
                                Send Message
                            </button>
                            </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('contactForm');
    const submitButton = document.getElementById('submitButton');
    const formspreeUrl = "https://formspree.io/f/xpwoenlz";
    
    // Check for success URL parameter from a previous full page redirect (now deprecated)
    if (new URLSearchParams(window.location.search).get('success') === '1') {
        const userName = "<?= htmlspecialchars($user_name ?? 'Guest') ?>";
        Swal.fire({
            icon: 'success',
            title: 'Message Sent! ✅',
            html: `Thank you, **${userName}**! Your message has been received. We will be in touch soon.`,
            confirmButtonText: 'Got It!',
            confirmButtonColor: '#198754'
        }).then(() => {
            if (history.replaceState) {
                history.replaceState(null, '', location.pathname);
            }
        });
    }

    // NEW AJAX SUBMISSION LOGIC
    form.addEventListener('submit', async function (e) {
        e.preventDefault(); // Stop the default form submission (which would cause a redirect)

        // Disable button and show a loading state
        submitButton.disabled = true;
        submitButton.textContent = 'Sending...';

        try {
            const formData = new FormData(form);
            
            // Remove the 'disabled' fields from the FormData if they exist
            if ('<?= $disabled_if_logged_in ?>' !== '') {
                // If the fields are disabled, their values won't be in FormData, 
                // but we should re-add the user's name/email to ensure Formspree receives them.
                // NOTE: The form fields are visible and populated, so this is mainly a safeguard.
                const userName = document.getElementById('name_input').value;
                const userEmail = document.getElementById('email_input').value;
                formData.set('name', userName);
                formData.set('_replyto', userEmail);
            }

            // Send the data to Formspree using fetch
            const response = await fetch(formspreeUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json' // Crucial for non-redirecting AJAX submission
                }
            });

            if (response.ok) {
                // SUCCESS: Show the custom SweetAlert2 message
                const userName = "<?= htmlspecialchars($user_name ?? 'Guest') ?>";
                Swal.fire({
                    icon: 'success',
                    title: 'Message Sent! ✅',
                    html: `Thank you, **${userName}**! Your message has been received. We will be in touch soon.`,
                    confirmButtonText: 'Got It!',
                    confirmButtonColor: '#198754'
                });
                form.reset(); // Clear the form fields after success

            } else {
                // ERROR: Log error and show a generic failure message
                console.error('Formspree submission failed:', response.statusText);
                Swal.fire({
                    icon: 'error',
                    title: 'Submission Failed ❌',
                    text: 'There was an issue sending your message. Please try again or contact us by email.',
                    confirmButtonColor: '#dc3545' // Bootstrap danger color
                });
            }

        } catch (error) {
            console.error('Network or Submission Error:', error);
             Swal.fire({
                icon: 'error',
                title: 'Network Error ⚠️',
                text: 'Could not connect to the server. Please check your internet connection and try again.',
                confirmButtonColor: '#dc3545'
            });
        } finally {
            // Re-enable button and reset text
            submitButton.disabled = false;
            submitButton.textContent = 'Send Message';
        }
    });
});
</script>