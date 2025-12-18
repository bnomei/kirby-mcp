<label>
    <input name="captcha" type="text" value="" required
           placeholder="Captcha"  pattern="[a-zA-Z0-9]{5}" autocomplete="off">
</label>
<figure>
    <img src="<?= kart()->urls()->captcha() ?>" alt="Captcha">
</figure>

