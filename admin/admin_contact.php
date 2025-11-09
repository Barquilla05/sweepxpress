<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/header.php';

// ✅ Mark as read (AJAX)
if (isset($_POST['mark_read'])) {
    $id = intval($_POST['mark_read']);
    $pdo->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = ?")->execute([$id]);
    exit;
}

// ✅ Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_ids'])) {
    $ids = $_POST['delete_ids'];
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id IN ($placeholders)");
        $stmt->execute($ids);

        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <script>
        document.addEventListener('DOMContentLoaded', () => {
          Swal.fire({
            icon: 'success',
            title: 'Deleted!',
            text: 'Selected messages have been deleted.',
            confirmButtonColor: '#d33'
          }).then(() => {
            window.location.href = 'admin_contact.php';
          });
        });
        </script>";
    }
}

// ✅ Handle reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_message'])) {
    $id = intval($_POST['message_id']);
    $reply = trim($_POST['reply_message']);

    $pdo->prepare("UPDATE contact_messages SET admin_reply = ?, replied_at = NOW() WHERE id = ?")
        ->execute([$reply, $id]);

    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
      Swal.fire({
        icon: 'success',
        title: 'Reply Sent!',
        text: 'Your reply has been successfully saved.',
        confirmButtonColor: '#3085d6'
      }).then(() => {
        window.location.href = 'admin_contact.php';
      });
    });
    </script>";
}

$stmt = $pdo->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
$messages = $stmt->fetchAll();
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="container my-5">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 m-0">📬 Contact Messages</h1>
    <div class="d-flex gap-2 align-items-center">
      <div id="deleteControls" style="display:none;">
        <button type="button" id="cancelDelete" class="btn btn-secondary me-2">
          <i class="bi bi-x-circle"></i> Cancel
        </button>
        <button type="button" id="confirmDelete" class="btn btn-danger">
          <i class="bi bi-check2-circle"></i> Confirm Delete
        </button>
      </div>
      <button id="toggleDelete" class="btn btn-outline-danger">
        <i class="bi bi-trash"></i> Delete Messages
      </button>
    </div>
  </div>

  <div class="card shadow-sm border-0">
    <div class="card-body">
      <form id="deleteForm" method="POST">
        <div class="table-responsive">
          <table class="table table-hover align-middle text-center">
            <thead class="table-primary">
              <tr>
                <th style="width: 40px; display:none;" class="select-col">
                  <input type="checkbox" id="selectAll">
                </th>
                <th>#</th>
                <th>Name</th>
                <th>Email</th>
                <th>Message</th>
                <th>Date</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($messages): ?>
                <?php foreach ($messages as $i => $msg): ?>
                  <tr class="message-row <?= $msg['is_read'] ? '' : 'table-danger' ?> <?= $msg['is_read'] ? '' : 'unread-row' ?>"
                      data-id="<?= $msg['id'] ?>"
                      data-name="<?= htmlspecialchars($msg['name']) ?>"
                      data-email="<?= htmlspecialchars($msg['email']) ?>"
                      data-message="<?= htmlspecialchars($msg['message']) ?>"
                      data-reply="<?= htmlspecialchars($msg['admin_reply']) ?>"
                      data-read="<?= $msg['is_read'] ?>">
                    <td style="display:none;" class="select-col">
                      <input type="checkbox" name="delete_ids[]" value="<?= $msg['id'] ?>">
                    </td>
                    <td><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($msg['name']) ?></td>
                    <td><?= htmlspecialchars($msg['email']) ?></td>
                    <td style="max-width: 250px;" class="text-truncate"><?= htmlspecialchars($msg['message']) ?></td>
                    <td><?= htmlspecialchars($msg['created_at']) ?></td>
                    <td>
                      <?php if ($msg['admin_reply']): ?>
                        <span class="badge bg-success text-light">Replied</span>
                      <?php elseif (!$msg['is_read']): ?>
                        <span class="badge bg-danger text-light">Unread</span>
                      <?php else: ?>
                        <span class="badge bg-warning text-dark">Read</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="7" class="text-muted py-4">No messages found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="messageModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Message from <span id="modalName"></span></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p><strong>Email:</strong> <span id="modalEmail"></span></p>
        <p id="modalMessage" class="mb-3 text-break"></p>
        <hr>
        <form method="POST">
          <input type="hidden" name="message_id" id="modalMessageId">
          <div class="mb-3">
            <label class="form-label">Admin Reply:</label>
            <textarea name="reply_message" id="modalReply" class="form-control" rows="4" placeholder="Type your reply..."></textarea>
          </div>
          <button type="submit" class="btn btn-success">Send Reply</button>
        </form>
      </div>
    </div>
  </div>
</div>

<style>
  .message-row:hover { background-color: #f8f9fa; cursor: pointer; }
  .table-danger { background-color: #ffe5e5 !important; }
  #modalMessage { white-space: pre-wrap; }
  .badge { font-weight: 600; color: #fff !important; }
  .bg-warning.text-dark { color: #000 !important; }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const modal = new bootstrap.Modal(document.getElementById('messageModal'));
  const modalName = document.getElementById('modalName');
  const modalEmail = document.getElementById('modalEmail');
  const modalMessage = document.getElementById('modalMessage');
  const modalReply = document.getElementById('modalReply');
  const modalId = document.getElementById('modalMessageId');

  const toggleDelete = document.getElementById('toggleDelete');
  const deleteControls = document.getElementById('deleteControls');
  const cancelDelete = document.getElementById('cancelDelete');
  const confirmDelete = document.getElementById('confirmDelete');
  const selectCols = document.querySelectorAll('.select-col');
  const checkboxes = document.querySelectorAll('input[name="delete_ids[]"]');
  const selectAll = document.getElementById('selectAll');
  const form = document.getElementById('deleteForm');

  let deleteMode = false;

  // 🗑️ Enable delete mode
  toggleDelete.addEventListener('click', () => {
    deleteMode = true;
    selectCols.forEach(c => c.style.display = 'table-cell');
    deleteControls.style.display = 'flex';
    toggleDelete.style.display = 'none';
  });

  // ❌ Cancel delete mode
  cancelDelete.addEventListener('click', () => {
    deleteMode = false;
    selectCols.forEach(c => c.style.display = 'none');
    deleteControls.style.display = 'none';
    toggleDelete.style.display = 'block';
    checkboxes.forEach(ch => ch.checked = false);
  });

  // ✅ Confirm delete
  confirmDelete.addEventListener('click', () => {
    const selected = Array.from(checkboxes).filter(ch => ch.checked);
    if (selected.length === 0) {
      Swal.fire({
        icon: 'warning',
        title: 'No Messages Selected',
        text: 'Please select at least one message to delete.'
      });
      return;
    }
    Swal.fire({
      title: 'Delete Selected Messages?',
      text: "This action cannot be undone!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Yes, delete them!'
    }).then(result => {
      if (result.isConfirmed) form.submit();
    });
  });

  // 🚫 Prevent checkboxes from opening modal
  checkboxes.forEach(ch => {
    ch.addEventListener('click', e => e.stopPropagation());
  });
  if (selectAll) selectAll.addEventListener('click', e => e.stopPropagation());

  // 📬 Handle message click
  document.querySelectorAll('.message-row').forEach(row => {
    row.addEventListener('click', e => {
      if (deleteMode) return; // stop modal in delete mode

      modalName.textContent = row.dataset.name;
      modalEmail.textContent = row.dataset.email;
      modalMessage.textContent = row.dataset.message;
      modalReply.value = row.dataset.reply || '';
      modalId.value = row.dataset.id;

      modal.show();

      // ✅ Mark as read via AJAX
      if (row.dataset.read === "0") {
        fetch("", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: "mark_read=" + row.dataset.id
        }).then(() => {
          row.dataset.read = "1";
          row.classList.remove('table-danger');
          const badge = row.querySelector('.badge');
          badge.classList.remove('bg-danger');
          badge.classList.add('bg-warning', 'text-dark');
          badge.textContent = "Read";
        });
      }
    });
  });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
