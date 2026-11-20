    </main>

</div>

<script
src="/assets/js/customer.js">
</script>

<?php

include
__DIR__ .
'/app-response-modal.php';

?>

<script
src="/assets/js/app-response-modal.js">
</script>

<?php if (isset($pageJs)): ?>

<script
src="/assets/js/<?= htmlspecialchars($pageJs) ?>">
</script>

<?php endif; ?>

</body>
</html>