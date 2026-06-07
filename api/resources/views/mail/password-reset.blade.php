<h1>Reset your Worksyne password</h1>

<p>Hello {{ $user->name }},</p>

<p>We received a request to reset your password. This link expires in 60 minutes and can only be used once.</p>

<p><a href="{{ $resetUrl }}">Choose a new password</a></p>

<p>If you did not request this change, you can ignore this email. Your password will remain unchanged.</p>
