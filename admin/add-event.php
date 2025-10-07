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
        // header("Location: manage-events.php");
        echo "<script>window.location.href='manage-events.php';</script>";
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
    
    // New duration fields
    $duration = $_POST['duration'] ?: null;
    $duration_unit = $_POST['duration_unit'] ?: null;
    
    // Validation
    if (empty($title) || empty($description) || empty($event_date) || empty($event_time) || empty($venue)) {
        $error = "Please fill in all required fields!";
    } else {
        // Handle image upload
        $image_path = $event['image_path'] ?? 'assets/images/event-placeholder.jpg';
        
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
                $stmt = $pdo->prepare("UPDATE events SET title=?, description=?, event_date=?, event_time=?, venue=?, organizer=?, category=?, max_participants=?, image_path=?, duration=?, duration_unit=? WHERE id=?");
                $success = $stmt->execute([$title, $description, $event_date, $event_time, $venue, $organizer, $category, $max_participants, $image_path, $duration, $duration_unit, $event_id]);
                $message = "Event updated successfully!";
            } else {
                // Insert new event
                $stmt = $pdo->prepare("INSERT INTO events (title, description, event_date, event_time, venue, organizer, category, max_participants, image_path, duration, duration_unit, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $success = $stmt->execute([$title, $description, $event_date, $event_time, $venue, $organizer, $category, $max_participants, $image_path, $duration, $duration_unit, $_SESSION['user_id']]);
                $message = "Event created successfully!";
            }
            
            if ($success) {
                $_SESSION['message'] = $message;
                $_SESSION['message_type'] = 'success';
                // header("Location: manage-events.php");
                echo "<script>window.location.href='manage-events.php';</script>";
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
        <div class="form-section">
            <h3>Event Image</h3>
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

        <!-- Basic Information -->
        <div class="form-section">
            <h3>Basic Information</h3>
            <div class="form-row">
                <div class="form-group">
                    <label>Event Title *</label>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($event['title'] ?? ''); ?>" required placeholder="Enter event title">
                </div>
                <div class="form-group">
                    <label>Category *</label>
                    <select name="category" required>
                        <option value="">Select Category</option>
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
                <textarea name="description" rows="5" required placeholder="Describe your event in detail..."><?php echo htmlspecialchars($event['description'] ?? ''); ?></textarea>
            </div>
        </div>

        <!-- Date & Time -->
        <div class="form-section">
            <h3>Date & Time</h3>
            <div class="form-row">
                <div class="form-group">
                    <label>Event Date *</label>
                    <input type="date" name="event_date" value="<?php echo $event['event_date'] ?? ''; ?>" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label>Event Time *</label>
                    <input type="time" name="event_time" value="<?php echo $event['event_time'] ?? ''; ?>" required>
                </div>
            </div>

            <!-- Duration Fields -->
            <div class="form-row">
                <div class="form-group">
                    <label>Duration (Optional)</label>
                    <div class="duration-fields">
                        <input type="number" name="duration" value="<?php echo $event['duration'] ?? ''; ?>" min="1" placeholder="Duration" class="duration-input">
                        <select name="duration_unit" class="duration-unit">
                            <option value="">Select Unit</option>
                            <option value="minutes" <?php echo ($event['duration_unit'] ?? '') === 'minutes' ? 'selected' : ''; ?>>Minutes</option>
                            <option value="hours" <?php echo ($event['duration_unit'] ?? '') === 'hours' ? 'selected' : ''; ?>>Hours</option>
                            <option value="days" <?php echo ($event['duration_unit'] ?? '') === 'days' ? 'selected' : ''; ?>>Days</option>
                            <option value="weeks" <?php echo ($event['duration_unit'] ?? '') === 'weeks' ? 'selected' : ''; ?>>Weeks</option>
                            <option value="months" <?php echo ($event['duration_unit'] ?? '') === 'months' ? 'selected' : ''; ?>>Months</option>
                        </select>
                    </div>
                    <small class="field-help">Leave empty for single-day events</small>
                </div>
                <div class="form-group">
                    <label>Maximum Participants (Optional)</label>
                    <input type="number" name="max_participants" value="<?php echo $event['max_participants'] ?? ''; ?>" min="1" placeholder="Leave empty for unlimited">
                </div>
            </div>
        </div>

        <!-- Location & Organizer -->
        <div class="form-section">
            <h3>Location & Organizer</h3>
            <div class="form-row">
                <div class="form-group">
                    <label>Venue *</label>
                    <input type="text" name="venue" value="<?php echo htmlspecialchars($event['venue'] ?? ''); ?>" required placeholder="Event location or venue">
                </div>
                <div class="form-group">
                    <label>Organizer</label>
                    <input type="text" name="organizer" value="<?php echo htmlspecialchars($event['organizer'] ?? $_SESSION['name'] ?? ''); ?>" placeholder="Event organizer name">
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary btn-large">
                <i class="fas fa-<?php echo $edit_mode ? 'save' : 'plus'; ?>"></i>
                <?php echo $edit_mode ? 'Update Event' : 'Create Event'; ?>
            </button>
            <a href="manage-events.php" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>

<style>
.admin-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 2rem;
}

.admin-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #eee;
}

.admin-header h1 {
    margin: 0;
    color: #2c3e50;
}

.form-section {
    background: white;
    padding: 2rem;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.form-section h3 {
    color: #2c3e50;
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #3498db;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.form-section h3::before {
    content: '';
    width: 4px;
    height: 20px;
    background: #3498db;
    border-radius: 2px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #2c3e50;
}

.form-group input,
.form-group select,
.form-group textarea {
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

.form-group textarea {
    resize: vertical;
    min-height: 120px;
}

/* Duration Fields */
.duration-fields {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.5rem;
}

.duration-input,
.duration-unit {
    width: 100%;
}

.field-help {
    color: #7f8c8d;
    font-size: 0.8rem;
    margin-top: 0.25rem;
}

/* Image Upload */
.image-upload-container {
    border: 2px dashed #ddd;
    border-radius: 10px;
    padding: 2rem;
    text-align: center;
    background: #f8f9fa;
    transition: all 0.3s ease;
}

.image-preview {
    margin-bottom: 1.5rem;
}

.image-preview img {
    max-width: 300px;
    max-height: 200px;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    object-fit: cover;
}

.no-image {
    padding: 3rem;
    color: #6c757d;
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    background: white;
    transition: all 0.3s ease;
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
    text-align: center;
}

.image-upload-container:hover {
    border-color: #3498db;
    background: #f0f8ff;
}

.image-upload-container.dragover {
    border-color: #3498db;
    background: #e3f2fd;
}

/* Form Actions */
.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    padding-top: 2rem;
    border-top: 1px solid #eee;
}

/* Responsive */
@media (max-width: 768px) {
    .admin-container {
        padding: 1rem;
    }
    
    .admin-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .duration-fields {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .image-preview img {
        max-width: 100%;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('event_image');
    let imagePreview = document.getElementById('imagePreview');
    const uploadContainer = document.querySelector('.image-upload-container');
    
    // Image preview functionality
    fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                if (imagePreview.classList.contains('no-image')) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.alt = 'Event image preview';
                    img.style.maxWidth = '300px';
                    img.style.maxHeight = '200px';
                    img.style.borderRadius = '8px';
                    img.style.boxShadow = '0 4px 8px rgba(0,0,0,0.1)';
                    img.style.objectFit = 'cover';
                    
                    imagePreview.parentNode.replaceChild(img, imagePreview);
                    imagePreview = img;
                } else {
                    imagePreview.src = e.target.result;
                }
            }
            
            reader.readAsDataURL(file);
        }
    });
    
    // Drag and drop functionality
    uploadContainer.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('dragover');
    });
    
    uploadContainer.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
    });
    
    uploadContainer.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            const event = new Event('change');
            fileInput.dispatchEvent(event);
        }
    });
    
    // Set minimum date to today
    const dateInput = document.querySelector('input[type="date"]');
    if (dateInput && !dateInput.value) {
        dateInput.min = new Date().toISOString().split('T')[0];
    }
    
    // Auto-fill organizer with current user's name if empty
    const organizerInput = document.querySelector('input[name="organizer"]');
    if (organizerInput && !organizerInput.value) {
        organizerInput.value = '<?php echo $_SESSION['name'] ?? ''; ?>';
    }
});
</script>

<?php include 'includes/admin-footer.php'; ?>