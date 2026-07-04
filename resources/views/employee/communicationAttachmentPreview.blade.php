<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $imageName }} | Communication</title>
    <style>
        * {
            box-sizing: border-box;
        }

        html,
        body {
            width: 100%;
            min-height: 100%;
            margin: 0;
            background: #09090b;
        }

        .attachment-viewer {
            position: fixed;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            cursor: pointer;
            text-decoration: none;
        }

        .attachment-viewer img {
            display: block;
            max-width: 100%;
            max-height: calc(100vh - 48px);
            object-fit: contain;
        }

        .back-label {
            position: fixed;
            top: 16px;
            left: 16px;
            padding: 9px 14px;
            border-radius: 999px;
            color: #fff;
            background: rgba(15, 23, 42, 0.78);
            font: 600 13px/1.2 Arial, sans-serif;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <a href="{{ $backUrl }}" class="attachment-viewer" aria-label="Return to communication">
        <span class="back-label">← Back to communication</span>
        <img src="{{ $imageUrl }}" alt="{{ $imageName }}">
    </a>
</body>
</html>
