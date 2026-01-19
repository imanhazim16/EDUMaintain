// Main Script
document.addEventListener('DOMContentLoaded', function () {
    console.log('UiTM EduMaintain Enhanced Loaded');

    // Auto-close alerts
    setTimeout(function () {
        let alerts = document.querySelectorAll('.alert');
        alerts.forEach(function (alert) {
            let bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);

    // Camera Logic
    const startCameraBtn = document.getElementById('start-camera');
    const takePhotoBtn = document.getElementById('capture-btn'); // Updated ID
    const video = document.getElementById('camera-stream');
    const canvas = document.getElementById('camera-canvas');
    const photoInput = document.getElementById('camera_image'); // Legacy ID, kept to avoid null reference if used elsewhere
    // photoPreview removed as it's replaced by gallery
    const cameraContainer = document.getElementById('camera-container');
    const previewContainer = document.getElementById('preview-container');
    const retakeBtn = document.getElementById('retake-btn');
    const fileInput = document.querySelector('input[name="images[]"]'); // Updated selector

    if (startCameraBtn) {
        let capturedPhotos = [];

        startCameraBtn.addEventListener('click', async function () {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: true });
                video.srcObject = stream;
                cameraContainer.style.display = 'block';
                video.play();
                startCameraBtn.style.display = 'none';
            } catch (err) {
                console.error("Error accessing camera: ", err);
                alert("Could not access camera.");
            }
        });

        takePhotoBtn.addEventListener('click', function () {
            const context = canvas.getContext('2d');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            context.drawImage(video, 0, 0, canvas.width, canvas.height);

            const dataURL = canvas.toDataURL('image/png');
            capturedPhotos.push(dataURL);

            // Update hidden input
            document.getElementById('camera_images').value = JSON.stringify(capturedPhotos); // Store as JSON string

            // Add thumbnail to gallery
            const gallery = document.getElementById('photo-gallery');
            const col = document.createElement('div');
            col.className = 'col-4 col-md-3 position-relative';
            col.innerHTML = `
                <img src="${dataURL}" class="img-fluid rounded border shadow-sm">
                <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 p-1 lh-1 rounded-circle" style="transform: translate(30%, -30%); width: 20px; height: 20px; font-size: 10px;" onclick="removePhoto('${dataURL}', this)">x</button>
            `;
            gallery.appendChild(col);

            // Camera stays open for multiple captures

            // Clear file input if photo taken
            if (fileInput) fileInput.value = '';
        });
    }
});

// Helper to remove photo from array
function removePhoto(dataURL, btnElement) {
    btnElement.parentElement.remove();
    let input = document.getElementById('camera_images');
    let photos = JSON.parse(input.value || '[]');
    photos = photos.filter(p => p !== dataURL);
    input.value = JSON.stringify(photos);
}
