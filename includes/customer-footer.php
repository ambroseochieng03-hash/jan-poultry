    </main>

</div>

<script
src="/jan-poultry/public/assets/js/customer.js">
</script>

<?php

include
__DIR__ .
'/app-response-modal.php';

?>

<script
src="/jan-poultry/public/assets/js/app-response-modal.js">
</script>

<?php if (isset($pageJs)): ?>

<script
src="/jan-poultry/public/assets/js/<?= htmlspecialchars($pageJs) ?>">
</script>

<?php endif; ?>

</body>
</html>