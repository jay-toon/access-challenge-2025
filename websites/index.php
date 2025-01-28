<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Test</title>
    <script>
        fetch('/api')
            .then(response => response.json())
            .then(data => {
                document.getElementById('api-status').textContent = data.message;
            })
            .catch(error => {
                document.getElementById('api-status').textContent = 'Error: ' + error.message;
            });
    </script>
</head>
<body>
    <h1>Welcome to the API Test Page</h1>
    <p>API Status: <span id="api-status">Checking...</span></p>
</body>
</html> 