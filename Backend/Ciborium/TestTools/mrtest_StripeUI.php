<html>
<body>
<div>
    Testing subscription monthly charge creation of token from Stripe UI.
</div>
<form action="mrtest_StripeUI_post.php" method="POST">
    <script
        src="https://checkout.stripe.com/v2/checkout.js" class="stripe-button"
        data-key="pk_test_czwzkTp2tactuLOEOqbMTRzG"
        data-amount="650"
        data-name="Test Stripe Prep"
        data-description="Monthly ($6.50)"
        data-image="/128x128.png">
    </script>
</form>
</body>
</html>
