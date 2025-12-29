<!DOCTYPE html>
<html>
<head>
    <style>
        body { margin: 0; padding: 0; }
        .page {
            page-break-after: always;
            text-align: center;
        }
        .page:last-child { page-break-after: never; }
        img {
            max-width: 100%;
            height: auto;
        }
    </style>
</head>
<body>
    @foreach($images as $img)
        <div class="page">
            <img src="{{ $img }}">
        </div>
    @endforeach
</body>
</html>