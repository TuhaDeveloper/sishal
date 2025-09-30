# Email Configuration Guide

## Overview
This application now includes a beautiful Order Confirmation email template that will be automatically sent when customers place orders.

## Email Configuration

### 1. Environment Variables (.env file)
Add these variables to your `.env` file:

```env
# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

### 2. Gmail Setup (Recommended)
1. Enable 2-Factor Authentication on your Gmail account
2. Generate an App Password:
   - Go to Google Account Settings
   - Security → 2-Step Verification → App passwords
   - Generate a new app password for "Mail"
3. Use the generated password in `MAIL_PASSWORD`

### 3. Alternative Email Providers

#### Mailgun
```env
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=your-domain.com
MAILGUN_SECRET=your-mailgun-secret
```

#### SendGrid
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your-sendgrid-api-key
```

#### Amazon SES
```env
MAIL_MAILER=ses
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=us-east-1
```

## Email Template Features

### Order Confirmation Email Includes:
- ✅ Beautiful responsive design
- ✅ Order details and customer information
- ✅ Itemized product list with prices
- ✅ Order summary with totals
- ✅ Payment status information
- ✅ Next steps for customers
- ✅ Contact information

### Template Location:
- **Email Class**: `app/Mail/OrderConfirmation.php`
- **Email Template**: `resources/views/emails/order-confirmation.blade.php`

## Testing Email Functionality

### 1. Test Route (Development Only)
Visit: `http://your-domain.com/test-email/{orderId}`

Replace `{orderId}` with an actual order ID from your database.

### 2. Manual Testing
```php
// In tinker or a test route
$order = \App\Models\Order::with(['items.product', 'customer'])->find(1);
\Illuminate\Support\Facades\Mail::to('test@example.com')->send(new \App\Mail\OrderConfirmation($order));
```

### 3. Log Driver (For Development)
To see emails in logs instead of sending them:
```env
MAIL_MAILER=log
```

Check logs at: `storage/logs/laravel.log`

## Email Sending Logic

### When Emails Are Sent:
- ✅ **Order Creation**: Automatically sent when a customer places an order
- ✅ **Error Handling**: If email fails, order creation still succeeds
- ✅ **Logging**: Email failures are logged for debugging

### Email Recipients:
- Primary: Customer's email address from the order
- Fallback: Can be configured in the email class

## Customization

### 1. Update Company Information
Edit the footer in `resources/views/emails/order-confirmation.blade.php`:
```html
<div class="contact-info">
    <p><strong>Need Help?</strong></p>
    <p>Email: your-support@company.com</p>
    <p>Phone: +1 (555) 123-4567</p>
    <p>Hours: Monday - Friday, 9 AM - 6 PM EST</p>
</div>
```

### 2. Update Email Subject
Edit in `app/Mail/OrderConfirmation.php`:
```php
public function envelope(): Envelope
{
    return new Envelope(
        subject: 'Your Order Confirmation - #' . $this->order->order_number,
    );
}
```

### 3. Add More Email Templates
Create new mail classes following the same pattern:
```bash
php artisan make:mail ShipmentConfirmation
```

## Troubleshooting

### Common Issues:

1. **"Connection refused"**
   - Check SMTP settings
   - Verify port and encryption settings
   - Ensure firewall allows outbound SMTP

2. **"Authentication failed"**
   - Verify username/password
   - For Gmail, ensure you're using App Password, not regular password
   - Check if 2FA is enabled

3. **"Email not sending"**
   - Check logs: `storage/logs/laravel.log`
   - Verify email configuration
   - Test with log driver first

4. **"Template not found"**
   - Ensure template exists at `resources/views/emails/order-confirmation.blade.php`
   - Clear view cache: `php artisan view:clear`

### Debug Commands:
```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Test mail configuration
php artisan tinker
Mail::raw('Test email', function($message) { $message->to('test@example.com')->subject('Test'); });
```

## Security Notes

1. **Never commit email credentials** to version control
2. **Use environment variables** for all sensitive data
3. **Enable 2FA** on email accounts used for sending
4. **Use App Passwords** instead of regular passwords
5. **Monitor email logs** for any suspicious activity

## Production Deployment

1. **Update environment variables** with production email settings
2. **Test email functionality** before going live
3. **Set up email monitoring** and alerts
4. **Configure proper SPF/DKIM** records for your domain
5. **Monitor email delivery rates** and bounce rates

---

**Need Help?** Contact your development team or refer to Laravel's official mail documentation. 