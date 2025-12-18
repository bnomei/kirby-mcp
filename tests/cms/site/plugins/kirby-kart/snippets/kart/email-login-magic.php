Hi <?= $user->nameOrEmail() ?>,

You recently requested a login code for <?= $site ?>.
The following login code will be valid for <?= $timeout ?> minutes:

<?= $code ?>


If you did not request a login code, please ignore this email or contact the administrator if you have questions.
For security, please DO NOT forward this email.
