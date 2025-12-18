<form method="POST" action="<?= kart()->urls()->account_delete() ?>">
    <button type="submit"
            onclick="if(confirm('Are you sure you want to delete your account?')) { this.disabled=true; this.form.submit(); } return false;">Delete Account</button>
</form>
