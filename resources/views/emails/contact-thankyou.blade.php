<x-mail::message>
Hi {{ $firstName }},

Thank you for reaching out to **TelcoVantage Philippines**.

We've received your inquiry and our team will review your message shortly. A representative will get back to you within **1–2 business days** using the email address you provided.

Here's a copy of your message:

*"{{ $userMessage }}"*

In the meantime, you may visit our website to learn more about our services:

<x-mail::button :url="'https://telcovantage.com'" color="success">
Visit TelcoVantage Philippines
</x-mail::button>

Warm regards,<br>
**TelcoVantage Philippines**
</x-mail::message>
