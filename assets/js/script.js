/**
 * Citizen Issue Reporting System (CIRS)
 * Core Client-Side Interactivity & Maps
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Navbar Scroll Effect
    const navbar = document.querySelector('.cirs-navbar');
    if (navbar) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 20) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    }

    // 2. Auto-Dismiss Alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) bsAlert.close();
        }, 5000);
    });

    // 3. Password Show/Hide Toggle
    const togglePasswordBtns = document.querySelectorAll('.toggle-password');
    togglePasswordBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const icon = this.querySelector('i');
            if (input) {
                if (input.type === 'password') {
                    input.type = 'text';
                    if (icon) {
                        icon.classList.remove('bi-eye');
                        icon.classList.add('bi-eye-slash');
                    }
                } else {
                    input.type = 'password';
                    if (icon) {
                        icon.classList.remove('bi-eye-slash');
                        icon.classList.add('bi-eye');
                    }
                }
            }
        });
    });

    // 4. Image Upload Preview & Validation
    const imageInput = document.getElementById('complaint_image');
    const imagePreviewBox = document.getElementById('image_preview_box');
    const imagePreviewElem = document.getElementById('image_preview');
    const uploadPlaceholder = document.getElementById('upload_placeholder');

    if (imageInput && imagePreviewBox) {
        imageInput.addEventListener('change', function () {
            const file = this.files[0];
            if (file) {
                // Validate size (max 5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('Image size exceeds 5MB limit. Please choose a smaller photo.');
                    this.value = '';
                    return;
                }
                // Validate format
                const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
                if (!validTypes.includes(file.type)) {
                    alert('Invalid image format. Allowed formats: JPG, JPEG, PNG, WEBP.');
                    this.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (e) {
                    if (imagePreviewElem) {
                        imagePreviewElem.src = e.target.result;
                        imagePreviewElem.classList.remove('d-none');
                    }
                    if (uploadPlaceholder) {
                        uploadPlaceholder.classList.add('d-none');
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // 5. Intelligent Department Suggestion on Category Change (in Admin forms)
    const categorySelect = document.getElementById('category');
    const deptSelect = document.getElementById('department_id');
    const deptSuggestionBadge = document.getElementById('dept_suggestion_badge');

    const categoryDeptMap = {
        'Pothole': 1,         // Road / Public Works
        'Garbage': 2,         // Sanitation
        'Water Leakage': 3,   // Water Supply
        'Streetlight': 4,     // Electricity
        'Drainage': 5,        // Drainage
        'Illegal Dumping': 2, // Sanitation
        'Other': 6            // Municipal Administration
    };

    if (categorySelect && deptSelect) {
        categorySelect.addEventListener('change', function () {
            const selectedCat = this.value;
            if (categoryDeptMap[selectedCat]) {
                deptSelect.value = categoryDeptMap[selectedCat];
                if (deptSuggestionBadge) {
                    deptSuggestionBadge.classList.remove('d-none');
                    deptSuggestionBadge.innerHTML = `<i class="bi bi-magic me-1"></i>Auto-suggested for ${selectedCat}`;
                }
            }
        });
    }

    // 6. Copy to Clipboard Helper
    window.copyToClipboard = function (text, btnElement) {
        if (!navigator.clipboard) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            showCopyFeedback(btnElement);
            return;
        }

        navigator.clipboard.writeText(text).then(() => {
            showCopyFeedback(btnElement);
        }).catch(err => {
            console.error('Failed to copy: ', err);
        });
    };

    function showCopyFeedback(btn) {
        if (!btn) return;
        const origHtml = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check2 text-success"></i> Copied!';
        setTimeout(() => {
            btn.innerHTML = origHtml;
        }, 2000);
    }

    // 7. Client-Side Table Search / Filter
    const tableSearchInput = document.getElementById('tableSearchInput');
    if (tableSearchInput) {
        tableSearchInput.addEventListener('keyup', function () {
            const query = this.value.toLowerCase();
            const tableRows = document.querySelectorAll('.searchable-table tbody tr');
            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    }
});

/**
 * Initialize OpenStreetMap / Leaflet Location Picker
 */
function initLocationPicker(mapElementId, latInputId, lngInputId, addressInputId, defaultLat = 19.0760, defaultLng = 72.8777) {
    const mapContainer = document.getElementById(mapElementId);
    if (!mapContainer || typeof L === 'undefined') return;

    const latInput = document.getElementById(latInputId);
    const lngInput = document.getElementById(lngInputId);
    const addressInput = document.getElementById(addressInputId);

    const initialLat = latInput && latInput.value ? parseFloat(latInput.value) : defaultLat;
    const initialLng = lngInput && lngInput.value ? parseFloat(lngInput.value) : defaultLng;

    const map = L.map(mapElementId).setView([initialLat, initialLng], 14);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19
    }).addTo(map);

    // Add draggable marker
    const marker = L.marker([initialLat, initialLng], { draggable: true }).addTo(map);

    function updateCoordinates(lat, lng, fetchAddress = true) {
        if (latInput) latInput.value = lat.toFixed(7);
        if (lngInput) lngInput.value = lng.toFixed(7);

        if (fetchAddress && addressInput && (!addressInput.value || addressInput.getAttribute('data-autofilled') === 'true')) {
            // Free OpenStreetMap Nominatim reverse geocode
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
                .then(res => res.json())
                .then(data => {
                    if (data && data.display_name) {
                        addressInput.value = data.display_name;
                        addressInput.setAttribute('data-autofilled', 'true');
                    }
                })
                .catch(e => console.log('Reverse geocoding error:', e));
        }
    }

    // Marker drag event
    marker.on('dragend', function (e) {
        const position = marker.getLatLng();
        updateCoordinates(position.lat, position.lng, true);
    });

    // Map click event
    map.on('click', function (e) {
        marker.setLatLng(e.latlng);
        updateCoordinates(e.latlng.lat, e.latlng.lng, true);
    });

    // Geolocation "Locate Me" button
    const locateBtn = document.getElementById('btn_locate_me');
    if (locateBtn && navigator.geolocation) {
        locateBtn.addEventListener('click', () => {
            locateBtn.disabled = true;
            locateBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Locating...';
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    const userLat = pos.coords.latitude;
                    const userLng = pos.coords.longitude;
                    map.setView([userLat, userLng], 16);
                    marker.setLatLng([userLat, userLng]);
                    updateCoordinates(userLat, userLng, true);
                    locateBtn.disabled = false;
                    locateBtn.innerHTML = '<i class="bi bi-geo-alt-fill text-danger me-1"></i> Current Location Found';
                },
                (err) => {
                    alert('Could not retrieve your location. Please drop a pin directly on the map.');
                    locateBtn.disabled = false;
                    locateBtn.innerHTML = '<i class="bi bi-geo-alt-fill me-1"></i> Use My Current Location';
                },
                { timeout: 10000, enableHighAccuracy: true }
            );
        });
    }
}

/**
 * Initialize View-Only OpenStreetMap
 */
function initLocationView(mapElementId, lat, lng, popupText = 'Complaint Location') {
    const mapContainer = document.getElementById(mapElementId);
    if (!mapContainer || typeof L === 'undefined' || !lat || !lng) return;

    const map = L.map(mapElementId, { zoomControl: true, scrollWheelZoom: false }).setView([lat, lng], 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    L.marker([lat, lng]).addTo(map).bindPopup(`<b>${popupText}</b>`).openPopup();
}
