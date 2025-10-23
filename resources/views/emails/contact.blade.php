<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> </title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .email-container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 2px solid var(--primary-blue); 
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: var(--primary-blue);
            margin: 0;
            font-size: 24px;
        }
        .contact-info {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .contact-info h3 {
            color: var(--primary-blue);
            margin-top: 0;
            margin-bottom: 15px;
        }
        .info-row {
            display: flex;
            margin-bottom: 10px;
        }
        .info-label {
            font-weight: bold;
            width: 120px;
            color: #555;
        }
        .info-value {
            flex: 1;
            color: #333;
        }
        .message-section {
            background-color: #fff;
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .message-section h3 {
            color: #007bff;
            margin-top: 0;
            margin-bottom: 15px;
        }
        .message-content {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            white-space: pre-wrap;
            line-height: 1.6;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
            font-size: 14px;
        }
        .timestamp {
            color: #888;
            font-size: 12px;
            text-align: right;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>New Contact Form Submission</h1>
        </div>

        <div class="contact-info">
            <h3>Contact Details</h3>
            <div class="info-row">
                <div class="info-label">Name:</div>
                <div class="info-value">{{ $contactData['full_name'] }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Phone:</div>
                <div class="info-value">{{ $contactData['phone_number'] }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Subject:</div>
                <div class="info-value">{{ $contactData['subject'] }}</div>
            </div>
        </div>

        <div class="message-section">
            <h3>Message</h3>
            <div class="message-content">{{ $contactData['message'] }}</div>
        </div>

        <div class="timestamp">
            Received on: {{ now()->format('F j, Y \a\t g:i A') }}
        </div>

        <div class="footer">
            <p>This message was sent from your website's contact form.</p>
            <p>Please respond to the customer as soon as possible.</p>
        </div>
    </div>
</body>
</html>
