<?php
include 'includes/admin-header.php';

$edit_mode = false;
$event = null;

// Check if editing existing event
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $event_id = $_GET['edit'];
    $event = getEventById($event_id);
    
    if (!$event || ($event['created_by'] != $_SESSION['user_id'] && !isAdmin())) {
        $_SESSION['message'] = "Event not found or access denied!";
        $_SESSION['message_type'] = 'error';
        header("Location: manage-events.php");
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $event_date = $_POST['event_date'];
    $event_time = $_POST['event_time'];
    $venue = trim($_POST['venue']);
    $organizer = trim($_POST['organizer']);
    $category = $_POST['category'];
    $max_participants = $_POST['max_participants'] ?: null;
    
    // Validation
    if (empty($title) || empty($description) || empty($event_date) || empty($event_time) || empty($venue)) {
        $error = "Please fill in all required fields!";
    } else {
        // Handle image upload
        $image_path = $event['image_path'] ?? 'assets/images/event-placeholder.jpg'; // Keep existing image if editing
        
        if (isset($_FILES['event_image']) && $_FILES['event_image']['error'] === UPLOAD_ERR_OK) {
            $upload_result = handleImageUpload($_FILES['event_image']);
            if ($upload_result['success']) {
                $image_path = $upload_result['file_path'];
                
                // Delete old image if it's not the placeholder and we're editing
                if ($edit_mode && $event['image_path'] && $event['image_path'] !== 'assets/images/event-placeholder.jpg') {
                    if (file_exists($event['image_path'])) {
                        unlink($event['image_path']);
                    }
                }
            } else {
                $error = $upload_result['error'];
            }
        }
        
        if (!isset($error)) {
            if ($edit_mode) {
                // Update existing event
                $stmt = $pdo->prepare("UPDATE events SET title=?, description=?, event_date=?, event_time=?, venue=?, organizer=?, category=?, max_participants=?, image_path=? WHERE id=?");
                $success = $stmt->execute([$title, $description, $event_date, $event_time, $venue, $organizer, $category, $max_participants, $image_path, $event_id]);
                $message = "Event updated successfully!";
            } else {
                // Insert new event
                $stmt = $pdo->prepare("INSERT INTO events (title, description, event_date, event_time, venue, organizer, category, max_participants, image_path, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $success = $stmt->execute([$title, $description, $event_date, $event_time, $venue, $organizer, $category, $max_participants, $image_path, $_SESSION['user_id']]);
                $message = "Event created successfully!";
            }
            
            if ($success) {
                $_SESSION['message'] = $message;
                $_SESSION['message_type'] = 'success';
                header("Location: manage-events.php");
                exit();
            } else {
                $error = "Failed to save event! Please try again.";
            }
        }
    }
}

// Function to handle image upload
function handleImageUpload($file) {
    $upload_dir = '../assets/uploads/events/';
    
    // Create upload directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Validate file type
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $file_type = $file['type'];
    
    if (!in_array($file_type, $allowed_types)) {
        return ['success' => false, 'error' => 'Only JPG, PNG, GIF, and WebP images are allowed.'];
    }
    
    // Validate file size (max 5MB)
    $max_size = 5 * 1024 * 1024; // 5MB in bytes
    if ($file['size'] > $max_size) {
        return ['success' => false, 'error' => 'Image size must be less than 5MB.'];
    }
    
    // Generate unique filename
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $file_extension;
    $file_path = $upload_dir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        return [
            'success' => true, 
            'file_path' => 'assets/uploads/events/' . $filename,
            'filename' => $filename
        ];
    } else {
        return ['success' => false, 'error' => 'Failed to upload image. Please try again.'];
    }
}
?>

<div class="admin-container">
    <div class="admin-header">
        <h1><?php echo $edit_mode ? 'Edit Event' : 'Add New Event'; ?></h1>
        <a href="manage-events.php" class="btn btn-outline">← Back to Events</a>
    </div>

    <?php if(isset($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" class="admin-form" enctype="multipart/form-data">
        <!-- Image Upload Section -->
        <div class="form-group">
            <label>Event Image</label>
            <div class="image-upload-container">
                <div class="image-preview">
                    <?php if ($edit_mode && isset($event['image_path']) && $event['image_path'] !== 'assets/images/event-placeholder.jpg'): ?>
                        <img src="../<?php echo $event['image_path']; ?>" alt="Current event image" id="imagePreview">
                    <?php else: ?>
                        <div class="no-image" id="imagePreview">
                            <i class="fas fa-image"></i>
                            <span>No image selected</span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="upload-controls">
                    <input type="file" name="event_image" id="event_image" accept="image/*" class="file-input">
                    <label for="event_image" class="btn btn-outline">
                        <i class="fas fa-upload"></i> Choose Image
                    </label>
                    <div class="upload-info">
                        <small>Recommended: 800x400px JPG, PNG, or WebP (Max: 5MB)</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Event Title *</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($event['title'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label>Category *</label>
                <select name="category" required>
                    <option value="academic" <?php echo ($event['category'] ?? '') === 'academic' ? 'selected' : ''; ?>>Academic</option>
                    <option value="sports" <?php echo ($event['category'] ?? '') === 'sports' ? 'selected' : ''; ?>>Sports</option>
                    <option value="cultural" <?php echo ($event['category'] ?? '') === 'cultural' ? 'selected' : ''; ?>>Cultural</option>
                    <option value="workshop" <?php echo ($event['category'] ?? '') === 'workshop' ? 'selected' : ''; ?>>Workshop</option>
                    <option value="other" <?php echo ($event['category'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Description *</label>
            <textarea name="description" rows="5" required><?php echo htmlspecialchars($event['description'] ?? ''); ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Event Date *</label>
                <input type="date" name="event_date" value="<?php echo $event['event_date'] ?? ''; ?>" required>
            </div>
            <div class="form-group">
                <label>Event Time *</label>
                <input type="time" name="event_time" value="<?php echo $event['event_time'] ?? ''; ?>" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Venue *</label>
                <input type="text" name="venue" value="<?php echo htmlspecialchars($event['venue'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label>Organizer</label>
                <input type="text" name="organizer" value="<?php echo htmlspecialchars($event['organizer'] ?? ''); ?>">
            </div>
        </div>

        <div class="form-group">
            <label>Maximum Participants (optional)</label>
            <input type="number" name="max_participants" value="<?php echo $event['max_participants'] ?? ''; ?>" min="1">
        </div>

        <button type="submit" class="btn btn-primary btn-large">
            <?php echo $edit_mode ? 'Update Event' : 'Create Event'; ?>
        </button>
    </form>
</div>

<style>
.image-upload-container {
    border: 2px dashed #ddd;
    border-radius: 10px;
    padding: 2rem;
    text-align: center;
    background: #f8f9fa;
}

.image-preview {
    margin-bottom: 1.5rem;
}

.image-preview img {
    max-width: 300px;
    max-height: 200px;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.no-image {
    padding: 3rem;
    color: #6c757d;
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    background: white;
}

.no-image i {
    font-size: 3rem;
    display: block;
    margin-bottom: 0.5rem;
}

.upload-controls {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
}

.file-input {
    display: none;
}

.upload-info {
    color: #6c757d;
    font-size: 0.875rem;
}

.image-upload-container:hover {
    border-color: #3498db;
    background: #f0f8ff;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('event_image');
    const imagePreview = document.getElementById('imagePreview');
    
    fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                // Replace no-image div with actual image
                if (imagePreview.classList.contains('no-image')) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.alt = 'Event image preview';
                    img.style.maxWidth = '300px';
                    img.style.maxHeight = '200px';
                    img.style.borderRadius = '8px';
                    img.style.boxShadow = '0 4px 8px rgba(0,0,0,0.1)';
                    
                    imagePreview.parentNode.replaceChild(img, imagePreview);
                    imagePreview = img;
                } else {
                    imagePreview.src = e.target.result;
                }
            }
            
            reader.readAsDataURL(file);
        }
    });
    
    // Add drag and drop functionality
    const uploadContainer = document.querySelector('.image-upload-container');
    
    uploadContainer.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.style.borderColor = '#3498db';
        this.style.background = '#e3f2fd';
    });
    
    uploadContainer.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.style.borderColor = '#ddd';
        this.style.background = '#f8f9fa';
    });
    
    uploadContainer.addEventListener('drop', function(e) {
        e.preventDefault();
        this.style.borderColor = '#ddd';
        this.style.background = '#f8f9fa';
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            
            // Trigger change event
            const event = new Event('change');
            fileInput.dispatchEvent(event);
        }
    });
});
</script>

<?php include 'includes/admin-footer.php'; ?>