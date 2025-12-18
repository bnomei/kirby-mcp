Hi <?= $user->nameOrEmail() ?>,<br>
<br>
You recently requested a login code for <?= $site ?>.<br>
The following login link will be valid for <?= $timeout ?> minutes:<br>
<br>
<a href="<?= $code ?>"
   style="border-radius: 8px; padding: 10px 20px; background-color: #000; color: #fff; text-decoration: none; display: inline-block;">Login</a><br>
<br>
or copy this URL into your browser:<br>
<br>
<?= $code ?><br>
<br>
If you did not request a login code, please ignore this email or contact the administrator if you have questions.<br>
For security, please DO NOT forward this email.<br>