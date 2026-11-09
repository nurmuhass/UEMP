<p<?= $workflowError ? ' class="portal-notice portal-notice-error" role="alert"' : '' ?>><?= e($data['message']) ?></p>
<a href="index.php"<?= $workflowError ? ' class="btn portal-primary"' : '' ?>>Return to the portal</a>
