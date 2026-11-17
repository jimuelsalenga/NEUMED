<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body style="background: #f7f9fc; padding-bottom: 80px;">
    <div class="p-3 border-bottom bg-white">
        <h6 class="m-0 fw-bold text-center">Help Center</h6>
    </div>

    <div class="container mt-3">
        <div class="accordion border-0" id="faq">
            <div class="accordion-item mb-2 border-0 shadow-sm rounded">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed rounded" type="button" data-bs-toggle="collapse" data-bs-target="#q1">
                        How to upload X-ray?
                    </button>
                </h2>
                <div id="q1" class="accordion-collapse collapse" data-bs-parent="#faq">
                    <div class="accordion-body small text-muted">
                        Go to Home, tap the X-ray card, and select your file or use your camera to scan.
                    </div>
                </div>
            </div>
            </div>

        <div class="mt-4 p-4 text-center bg-white shadow-sm rounded-4">
            <i class="fas fa-robot fa-2x text-primary mb-2"></i>
            <h6>Chat with Support</h6>
            <p class="small text-muted">Available 8 AM - 5 PM</p>
            <button class="btn btn-outline-primary btn-sm px-4 rounded-pill">Start Chat</button>
        </div>
    </div>

    <?php include('includes/navbar.php'); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>