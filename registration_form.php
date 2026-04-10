<?php
// ============================================================
// STEP 1: PHP LOGIC - This runs FIRST before any HTML is shown
// ============================================================

$successData = null;  // Will hold submitted data if form was submitted
$errors      = [];    // Will hold any error messages

// Check if the form was submitted (POST request)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ----------------------------------------------------------
    // STEP 2: COLLECT & SANITIZE TEXT INPUTS FROM $_POST
    // ----------------------------------------------------------
    $name    = trim($_POST['name']    ?? '');
    $email   = trim($_POST['email']   ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = $_POST['password']    ?? '';

    // ----------------------------------------------------------
    // STEP 3: BASIC VALIDATION
    // ----------------------------------------------------------
    if (empty($name))                        $errors[] = "Full name is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email is required.";
    if (empty($address))                     $errors[] = "Permanent address is required.";
    if (strlen($password) < 6)               $errors[] = "Password must be at least 6 characters.";

    // ----------------------------------------------------------
    // STEP 4: HANDLE FILE UPLOAD FROM $_FILES
    // ----------------------------------------------------------
    $uploadedImagePath = '';

    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {

        $file       = $_FILES['profile_image'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize    = 2 * 1024 * 1024; // 2 MB

        // Validate file type using finfo (safer than trusting browser MIME)
        $finfo    = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!in_array($mimeType, $allowedTypes)) {
            $errors[] = "Only JPG, PNG, GIF, or WEBP images are allowed.";
        } elseif ($file['size'] > $maxSize) {
            $errors[] = "Image must be smaller than 2 MB.";
        } else {
            // Build a safe unique filename so no two uploads collide
            $ext          = pathinfo($file['name'], PATHINFO_EXTENSION);
            $safeFilename = uniqid('img_', true) . '.' . strtolower($ext);

            // Create uploads/ folder if it doesn't exist yet
            $uploadDir = __DIR__ . '/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $destination = $uploadDir . $safeFilename;

            // move_uploaded_file moves the temp file to our uploads folder
            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                $errors[] = "Image upload failed. Check folder permissions.";
            } else {
                $uploadedImagePath = 'uploads/' . $safeFilename;
            }
        }
    } else {
        $errors[] = "Profile image is required.";
    }

    // ----------------------------------------------------------
    // STEP 5: IF NO ERRORS, SAVE DATA (in real apps: save to DB)
    // ----------------------------------------------------------
    if (empty($errors)) {
        $successData = [
            'name'    => htmlspecialchars($name),
            'email'   => htmlspecialchars($email),
            'address' => htmlspecialchars($address),
            // NEVER store plain-text passwords – we hash with bcrypt
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'image'   => $uploadedImagePath,
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>User Registration</title>

    <!-- Bootstrap 5 CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    />
    <!-- Bootstrap Icons -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    />
    <!-- Google Font -->
    <link
        href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;600;700&family=Inter:wght@300;400;500&display=swap"
        rel="stylesheet"
    />

    <!-- ========================================================
         STEP 6: CUSTOM CSS – Dark Blue Theme + Animations
         ======================================================== -->
    <style>
        /* ---- CSS Variables (change these to retheme everything) ---- */
        :root {
            --navy:        #0a0e1f;
            --deep:        #0d1630;
            --mid:         #112250;
            --accent:      #1a6cf0;
            --accent-glow: #3b8ff5;
            --accent-light:#60a5fa;
            --card-bg:     rgba(13, 22, 48, 0.85);
            --border:      rgba(26, 108, 240, 0.35);
            --text:        #e2e8f0;
            --muted:       #94a3b8;
            --success:     #22d3a5;
            --error:       #f87171;
        }

        /* ---- Base ---- */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--navy);
            color: var(--text);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ---- Animated Starfield Background ---- */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 80% 60% at 50% -10%, rgba(26,108,240,.18) 0%, transparent 70%),
                radial-gradient(ellipse 60% 40% at 80% 90%,  rgba(26,108,240,.10) 0%, transparent 60%);
            pointer-events: none;
            z-index: 0;
        }

        /* Floating orbs */
        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: .18;
            pointer-events: none;
            animation: floatOrb 12s ease-in-out infinite alternate;
        }
        .orb-1 { width:420px; height:420px; background:#1a6cf0; top:-80px;  left:-100px; animation-delay:0s; }
        .orb-2 { width:300px; height:300px; background:#0e4faa; bottom:-60px; right:-60px;  animation-delay:4s; }
        .orb-3 { width:200px; height:200px; background:#3b8ff5; top:40%;   left:55%;    animation-delay:2s; }

        @keyframes floatOrb {
            from { transform: translate(0,0) scale(1); }
            to   { transform: translate(30px, 20px) scale(1.08); }
        }

        /* ---- Grid dots texture ---- */
        .grid-texture {
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(26,108,240,.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(26,108,240,.05) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
            z-index: 0;
        }

        /* ---- Page wrapper ---- */
        .page-wrap {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 3rem 1rem;
        }

        /* ---- Header ---- */
        .site-header {
            text-align: center;
            margin-bottom: 2.5rem;
            animation: slideDown .7s ease both;
        }
        .site-header h1 {
            font-family: 'Rajdhani', sans-serif;
            font-size: clamp(2rem, 5vw, 3rem);
            font-weight: 700;
            letter-spacing: .08em;
            color: #fff;
            text-shadow: 0 0 40px rgba(26,108,240,.7);
        }
        .site-header p {
            color: var(--muted);
            font-size: .95rem;
            margin-top: .5rem;
            letter-spacing: .04em;
        }
        .header-line {
            width: 80px;
            height: 3px;
            background: linear-gradient(90deg, var(--accent), var(--accent-glow));
            margin: .8rem auto 0;
            border-radius: 2px;
            box-shadow: 0 0 12px var(--accent);
        }

        @keyframes slideDown {
            from { opacity:0; transform: translateY(-30px); }
            to   { opacity:1; transform: translateY(0); }
        }

        /* ---- Card ---- */
        .glass-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 20px;
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            box-shadow: 0 8px 48px rgba(10,14,31,.6), 0 0 0 1px rgba(26,108,240,.12);
            padding: 2.5rem 2.2rem;
            width: 100%;
            max-width: 560px;
            animation: fadeUp .8s ease both;
            transition: box-shadow .3s ease;
        }
        .glass-card:hover {
            box-shadow: 0 12px 60px rgba(26,108,240,.2), 0 0 0 1px rgba(26,108,240,.3);
        }

        @keyframes fadeUp {
            from { opacity:0; transform: translateY(40px); }
            to   { opacity:1; transform: translateY(0); }
        }

        /* ---- Form Section Labels ---- */
        .section-label {
            font-family: 'Rajdhani', sans-serif;
            font-size: .72rem;
            font-weight: 600;
            letter-spacing: .15em;
            text-transform: uppercase;
            color: var(--accent-light);
            margin-bottom: 1.5rem;
            padding-bottom: .5rem;
            border-bottom: 1px solid var(--border);
        }

        /* ---- Form Fields ---- */
        .form-label {
            font-size: .82rem;
            font-weight: 500;
            color: var(--muted);
            letter-spacing: .04em;
            margin-bottom: .4rem;
        }
        .input-wrap {
            position: relative;
            margin-bottom: 1.2rem;
        }
        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            font-size: 1rem;
            pointer-events: none;
            transition: color .2s;
        }
        .input-icon.top { top: 18px; transform: none; }

        .form-control, .form-select {
            background: rgba(255,255,255,.04) !important;
            border: 1px solid var(--border) !important;
            border-radius: 10px !important;
            color: var(--text) !important;
            padding: .7rem .9rem .7rem 2.6rem;
            font-size: .9rem;
            transition: border-color .25s, box-shadow .25s, background .25s;
        }
        textarea.form-control { padding-top: .75rem; resize: vertical; }
        .form-control:focus {
            border-color: var(--accent) !important;
            box-shadow: 0 0 0 3px rgba(26,108,240,.25), 0 0 16px rgba(26,108,240,.12) !important;
            background: rgba(26,108,240,.06) !important;
            outline: none;
        }
        .form-control:focus + .input-icon,
        .input-wrap:focus-within .input-icon { color: var(--accent-light); }
        .form-control::placeholder { color: rgba(148,163,184,.45); }

        /* File input custom style */
        .file-drop {
            border: 2px dashed var(--border);
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: border-color .25s, background .25s;
            background: rgba(255,255,255,.02);
            position: relative;
        }
        .file-drop:hover { border-color: var(--accent); background: rgba(26,108,240,.05); }
        .file-drop input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
        }
        .file-drop-icon { font-size: 2rem; color: var(--accent); }
        .file-drop p   { font-size: .82rem; color: var(--muted); margin-top: .4rem; }
        #preview-wrap  { margin-top: 1rem; display: none; text-align: center; }
        #img-preview   {
            width: 90px; height: 90px; border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--accent);
            box-shadow: 0 0 20px rgba(26,108,240,.4);
            animation: popIn .3s ease;
        }
        @keyframes popIn {
            from { transform: scale(.7); opacity:0; }
            to   { transform: scale(1);  opacity:1; }
        }

        /* ---- Submit Button ---- */
        .btn-submit {
            width: 100%;
            padding: .85rem;
            background: linear-gradient(135deg, var(--accent) 0%, #0e4faa 100%);
            border: none;
            border-radius: 10px;
            color: #fff;
            font-family: 'Rajdhani', sans-serif;
            font-size: 1.05rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: transform .2s, box-shadow .2s;
            box-shadow: 0 4px 24px rgba(26,108,240,.35);
            margin-top: .5rem;
        }
        .btn-submit::before {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,.18), transparent);
            transition: left .5s ease;
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 32px rgba(26,108,240,.5); }
        .btn-submit:hover::before { left: 100%; }
        .btn-submit:active { transform: translateY(0); }

        /* ---- Error Alert ---- */
        .alert-error {
            background: rgba(248,113,113,.1);
            border: 1px solid rgba(248,113,113,.35);
            border-radius: 10px;
            padding: 1rem 1.2rem;
            margin-bottom: 1.5rem;
            animation: shake .4s ease;
        }
        .alert-error li { color: var(--error); font-size: .85rem; margin-bottom: .25rem; }
        @keyframes shake {
            0%,100% { transform:translateX(0); }
            25%      { transform:translateX(-6px); }
            75%      { transform:translateX(6px); }
        }

        /* ---- SUCCESS CARD ---- */
        .result-card {
            width: 100%;
            max-width: 560px;
            animation: fadeUp .8s ease both;
        }
        .result-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .result-header .check-icon {
            width: 64px; height: 64px;
            background: linear-gradient(135deg, var(--success), #0ea875);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem; color: #fff;
            margin: 0 auto 1rem;
            box-shadow: 0 0 30px rgba(34,211,165,.4);
            animation: bounceIn .6s cubic-bezier(.175,.885,.32,1.275) both;
        }
        @keyframes bounceIn {
            from { transform: scale(0); opacity:0; }
            to   { transform: scale(1); opacity:1; }
        }
        .result-header h2 {
            font-family: 'Rajdhani', sans-serif;
            font-size: 1.8rem;
            color: #fff;
        }
        .result-header p { color: var(--muted); font-size: .9rem; }

        /* Profile image in result */
        .profile-avatar {
            width: 110px; height: 110px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--accent);
            box-shadow: 0 0 30px rgba(26,108,240,.5);
            display: block;
            margin: 0 auto 1.5rem;
        }
        .no-avatar {
            width: 110px; height: 110px;
            border-radius: 50%;
            background: var(--mid);
            display: flex; align-items: center; justify-content: center;
            font-size: 2.5rem; color: var(--muted);
            margin: 0 auto 1.5rem;
            border: 4px solid var(--border);
        }

        /* Data rows */
        .data-row {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: .9rem 1rem;
            border-radius: 10px;
            border: 1px solid var(--border);
            margin-bottom: .75rem;
            background: rgba(255,255,255,.025);
            transition: background .2s, border-color .2s, transform .2s;
        }
        .data-row:hover {
            background: rgba(26,108,240,.06);
            border-color: rgba(26,108,240,.5);
            transform: translateX(4px);
        }
        .data-row .icon {
            width: 36px; height: 36px;
            background: rgba(26,108,240,.15);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            color: var(--accent-light);
            font-size: 1rem;
            flex-shrink: 0;
        }
        .data-row .info .label {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: var(--muted);
        }
        .data-row .info .value {
            font-size: .95rem;
            color: var(--text);
            margin-top: .1rem;
            word-break: break-all;
        }
        .password-dots { letter-spacing: .25em; font-size: 1.1rem; }

        /* Register again button */
        .btn-back {
            display: inline-flex; align-items: center; gap: .5rem;
            margin-top: 1.5rem;
            background: transparent;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: .65rem 1.6rem;
            color: var(--muted);
            font-size: .9rem;
            cursor: pointer;
            transition: border-color .2s, color .2s, background .2s;
            text-decoration: none;
        }
        .btn-back:hover {
            border-color: var(--accent);
            color: var(--accent-light);
            background: rgba(26,108,240,.07);
        }

        /* ---- Password strength bar ---- */
        #strength-bar-wrap { margin-top: .4rem; }
        #strength-bar {
            height: 4px;
            border-radius: 2px;
            background: var(--border);
            transition: width .3s, background .3s;
            width: 0%;
        }
        #strength-text { font-size: .72rem; color: var(--muted); margin-top: .3rem; }

        /* ---- Responsive ---- */
        @media (max-width: 480px) {
            .glass-card { padding: 1.8rem 1.2rem; }
        }
    </style>
</head>
<body>

<!-- Background FX -->
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>
<div class="grid-texture"></div>

<div class="page-wrap">

    <!-- Site Header -->
    <header class="site-header">
        <h1><i class="bi bi-shield-lock-fill me-2"></i>User Registration</h1>
        <p>Fill in your details to create a secure account</p>
        <div class="header-line"></div>
    </header>

    <?php if ($successData): ?>
    <!-- ========================================================
         STEP 7: DISPLAY SUBMITTED DATA IN RESULT CARD
         ======================================================== -->
    <div class="result-card">
        <div class="glass-card">

            <div class="result-header">
                <div class="check-icon"><i class="bi bi-check-lg"></i></div>
                <h2>Registration Complete!</h2>
                <p>Your information has been saved successfully.</p>
            </div>

            <!-- Profile Image -->
            <?php if ($successData['image']): ?>
                <img src="<?= $successData['image'] ?>" alt="Profile" class="profile-avatar" />
            <?php else: ?>
                <div class="no-avatar"><i class="bi bi-person"></i></div>
            <?php endif; ?>

            <!-- Data rows -->
            <div class="data-row">
                <div class="icon"><i class="bi bi-person-fill"></i></div>
                <div class="info">
                    <div class="label">Full Name</div>
                    <div class="value"><?= $successData['name'] ?></div>
                </div>
            </div>

            <div class="data-row">
                <div class="icon"><i class="bi bi-envelope-fill"></i></div>
                <div class="info">
                    <div class="label">Email Address</div>
                    <div class="value"><?= $successData['email'] ?></div>
                </div>
            </div>

            <div class="data-row">
                <div class="icon"><i class="bi bi-geo-alt-fill"></i></div>
                <div class="info">
                    <div class="label">Permanent Address</div>
                    <div class="value"><?= nl2br($successData['address']) ?></div>
                </div>
            </div>

            <div class="data-row">
                <div class="icon"><i class="bi bi-key-fill"></i></div>
                <div class="info">
                    <div class="label">Password (bcrypt hash)</div>
                    <!-- Show only first 30 chars of hash for display -->
                    <div class="value" style="font-size:.75rem; color:var(--muted);">
                        <?= substr($successData['password_hash'], 0, 30) ?>…
                    </div>
                </div>
            </div>

            <div class="data-row">
                <div class="icon"><i class="bi bi-image-fill"></i></div>
                <div class="info">
                    <div class="label">Uploaded Image Path</div>
                    <div class="value" style="font-size:.8rem;"><?= $successData['image'] ?></div>
                </div>
            </div>

            <div class="text-center">
                <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn-back">
                    <i class="bi bi-arrow-left"></i> Register Another User
                </a>
            </div>

        </div>
    </div>

    <?php else: ?>
    <!-- ========================================================
         STEP 8: SHOW REGISTRATION FORM
         ======================================================== -->
    <div class="glass-card">

        <!-- Error messages -->
        <?php if (!empty($errors)): ?>
        <div class="alert-error">
            <ul class="mb-0 ps-3">
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!--
            enctype="multipart/form-data" is REQUIRED for file uploads.
            Without it, $_FILES will be empty.
        -->
        <form method="POST" action="" enctype="multipart/form-data" novalidate>

            <!-- ---- Personal Info ---- -->
            <div class="section-label"><i class="bi bi-person me-1"></i> Personal Information</div>

            <!-- Full Name -->
            <div class="input-wrap">
                <label class="form-label" for="name">Full Name</label>
                <div class="position-relative">
                    <input
                        type="text"
                        id="name"
                        name="name"
                        class="form-control"
                        placeholder="e.g. Ali Hassan"
                        value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                        required
                    />
                    <i class="bi bi-person input-icon"></i>
                </div>
            </div>

            <!-- Email -->
            <div class="input-wrap">
                <label class="form-label" for="email">Email Address</label>
                <div class="position-relative">
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        placeholder="you@example.com"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        required
                    />
                    <i class="bi bi-envelope input-icon"></i>
                </div>
            </div>

            <!-- Permanent Address -->
            <div class="input-wrap">
                <label class="form-label" for="address">Permanent Address</label>
                <div class="position-relative">
                    <textarea
                        id="address"
                        name="address"
                        class="form-control"
                        rows="3"
                        placeholder="House #, Street, City, Country"
                        required
                    ><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                    <i class="bi bi-geo-alt input-icon top"></i>
                </div>
            </div>

            <!-- ---- Security ---- -->
            <div class="section-label mt-3"><i class="bi bi-shield-lock me-1"></i> Security</div>

            <!-- Password -->
            <div class="input-wrap">
                <label class="form-label" for="password">Password</label>
                <div class="position-relative">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="Min. 6 characters"
                        required
                    />
                    <i class="bi bi-lock input-icon"></i>
                </div>
                <!-- Strength indicator -->
                <div id="strength-bar-wrap">
                    <div id="strength-bar"></div>
                    <div id="strength-text"></div>
                </div>
            </div>

            <!-- ---- Profile Image ---- -->
            <div class="section-label mt-3"><i class="bi bi-image me-1"></i> Profile Image</div>

            <label class="form-label">Upload Photo (JPG/PNG/GIF/WEBP, max 2 MB)</label>
            <div class="file-drop" id="drop-zone">
                <input type="file" name="profile_image" id="profile_image" accept="image/*" required />
                <div class="file-drop-icon"><i class="bi bi-cloud-arrow-up"></i></div>
                <p>Click or drag & drop your photo here</p>
            </div>

            <!-- Live image preview -->
            <div id="preview-wrap">
                <img id="img-preview" src="" alt="Preview" />
                <p style="font-size:.78rem; color:var(--muted); margin-top:.4rem;" id="file-name"></p>
            </div>

            <!-- Submit -->
            <button type="submit" class="btn-submit mt-4">
                <i class="bi bi-send-fill me-2"></i>Submit Registration
            </button>

        </form>
    </div>
    <?php endif; ?>

</div><!-- end .page-wrap -->

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- ============================================================
     STEP 9: JAVASCRIPT – Preview + Password Strength
     ============================================================ -->
<script>
// -- Live image preview when user picks a file --
document.getElementById('profile_image').addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function (e) {
        document.getElementById('img-preview').src = e.target.result;
        document.getElementById('preview-wrap').style.display = 'block';
        document.getElementById('file-name').textContent = file.name;
    };
    reader.readAsDataURL(file);  // Convert image to base64 for preview
});

// -- Password strength bar --
document.getElementById('password').addEventListener('input', function () {
    const val = this.value;
    const bar  = document.getElementById('strength-bar');
    const text = document.getElementById('strength-text');

    let score = 0;
    if (val.length >= 6)  score++;
    if (val.length >= 10) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    const levels = [
        { width: '0%',   color: 'transparent', label: '' },
        { width: '20%',  color: '#f87171',      label: 'Very Weak' },
        { width: '40%',  color: '#fb923c',      label: 'Weak' },
        { width: '60%',  color: '#facc15',      label: 'Fair' },
        { width: '80%',  color: '#34d399',      label: 'Strong' },
        { width: '100%', color: '#22d3a5',      label: 'Very Strong' },
    ];

    bar.style.width      = levels[score].width;
    bar.style.background = levels[score].color;
    text.textContent     = levels[score].label;
    text.style.color     = levels[score].color;
});
</script>
</body>
</html>
