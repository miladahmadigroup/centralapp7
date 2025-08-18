<!DOCTYPE html>
<html lang="fa" dir="rtl" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light only">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : getSetting('site_title', 'اپ مرکزی'); ?></title>
    
    <!-- Bootstrap 5 RTL CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    
    <!-- Font Awesome CDN -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- فونت وزیر CDN -->
    <link href="https://cdn.fontcdn.ir/Font/Persian/Vazir/Vazir.css" rel="stylesheet">
    
    <!-- استایل‌های سفارشی -->
    <link href="<?php echo BASE_URL; ?>/assets/css/style.css" rel="stylesheet">
    
    <style>
        /* Force Light Theme - Override all dark styles */
        :root {
            color-scheme: light only !important;
        }
        
        * {
            color-scheme: light only !important;
        }
        
        html,
        html[data-bs-theme="dark"],
        html[data-bs-theme="auto"] {
            color-scheme: light only !important;
        }
        
        body {
            font-family: 'Vazir', sans-serif;
            background-color: #ffffff !important;
            color: #212529 !important;
            color-scheme: light only !important;
        }
        
        .navbar-brand img {
            margin-left: 10px;
        }
        
        /* Override Bootstrap dark variables */
        .card,
        .modal-content,
        .dropdown-menu,
        .form-control,
        .btn,
        .table {
            background-color: #ffffff !important;
            color: #212529 !important;
        }
        
        .card-header,
        .modal-header {
            background-color: #f8f9fa !important;
            color: #212529 !important;
        }
        
        .navbar-dark {
            background-color: #343a40 !important;
        }
        
        .bg-dark {
            background-color: #343a40 !important;
        }
        
        /* Prevent any dark theme detection */
        @media (prefers-color-scheme: dark) {
            * {
                color-scheme: light only !important;
                background-color: initial !important;
            }
            
            body {
                background-color: #ffffff !important;
                color: #212529 !important;
            }
            
            .card {
                background-color: #ffffff !important;
                color: #212529 !important;
            }
            
            .form-control {
                background-color: #ffffff !important;
                color: #212529 !important;
                border-color: #ced4da !important;
            }
            
            input, textarea, select {
                background-color: #ffffff !important;
                color: #212529 !important;
            }
        }
    </style>
    
    <?php if (isset($additionalHead)): ?>
        <?php echo $additionalHead; ?>
    <?php endif; ?>
</head>
<body>