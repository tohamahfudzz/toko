// script.js
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('uploadForm') || document.querySelector('form');
  const namaEl = document.getElementById('nama');
  const hargaEl = document.getElementById('harga');
  const deskripsiEl = document.getElementById('deskripsi');
  const gambarEl = document.getElementById('gambar');
  const preview = document.getElementById('preview');
  const submitBtn = form.querySelector('button[type="submit"]');
  const errorContainerId = 'form-errors';

  // create error container if not present
  let errorContainer = document.getElementById(errorContainerId);
  if (!errorContainer) {
    errorContainer = document.createElement('div');
    errorContainer.id = errorContainerId;
    errorContainer.style.margin = '12px 0';
    form.insertBefore(errorContainer, form.firstChild);
  }

  const MAX_SIZE = 2 * 1024 * 1024; // 2MB
  const ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/webp'];
  const ALLOWED_EXT = ['jpg', 'jpeg', 'png', 'webp'];

  function clearErrors() {
    errorContainer.innerHTML = '';
  }

  function showErrors(errors) {
    clearErrors();
    const ul = document.createElement('ul');
    ul.style.color = '#b91c1c';
    ul.style.margin = '0';
    ul.style.paddingLeft = '18px';
    errors.forEach(e => {
      const li = document.createElement('li');
      li.textContent = e;
      ul.appendChild(li);
    });
    errorContainer.appendChild(ul);
    window.scrollTo({ top: errorContainer.offsetTop - 20, behavior: 'smooth' });
  }

  function validateFile(file) {
    if (!file) return '📷 File gambar wajib dipilih.';
    if (file.size > MAX_SIZE) return '⚠️ Ukuran file maksimal 2MB.';
    const ext = file.name.split('.').pop().toLowerCase();
    if (!ALLOWED_EXT.includes(ext)) return '❌ Ekstensi file tidak diperbolehkan. Gunakan JPG, PNG, atau WEBP.';
    if (file.type && !ALLOWED_MIME.includes(file.type)) return '❌ Tipe file tidak diperbolehkan.';
    return '';
  }

  // preview gambar
  gambarEl.addEventListener('change', () => {
    preview.innerHTML = '';
    clearErrors();
    const file = gambarEl.files[0];
    const err = validateFile(file);
    if (err) {
      showErrors([err]);
      gambarEl.value = '';
      return;
    }
    const img = document.createElement('img');
    img.style.maxWidth = '220px';
    img.style.maxHeight = '220px';
    img.style.borderRadius = '8px';
    img.style.objectFit = 'cover';
    img.alt = 'Preview gambar';
    img.src = URL.createObjectURL(file);
    preview.appendChild(img);
  });

  // sanitize harga input to digits only
  hargaEl.addEventListener('input', (e) => {
    const cleaned = e.target.value.replace(/[^\d]/g, '');
    e.target.value = cleaned;
  });

  // basic validateForm fallback for inline onsubmit attribute
  window.validateForm = function() {
    clearErrors();
    const errors = [];
    const nama = namaEl.value.trim();
    const harga = hargaEl.value.trim();
    const deskripsi = deskripsiEl.value.trim();
    const file = gambarEl.files[0];

    if (!nama) errors.push('Nama produk wajib diisi.');
    if (!harga || isNaN(Number(harga)) || Number(harga) <= 0) errors.push('Harga harus berupa angka lebih dari 0.');
    if (!deskripsi) errors.push('Deskripsi wajib diisi.');

    const fileErr = validateFile(file);
    if (fileErr) errors.push(fileErr);

    if (errors.length) {
      showErrors(errors);
      return false;
    }
    return true;
  };

  // prevent double submit and validate before submit
  form.addEventListener('submit', (e) => {
    // if form has inline onsubmit that returns false, let it run first
    if (typeof window.validateForm === 'function') {
      const ok = window.validateForm();
      if (!ok) {
        e.preventDefault();
        return false;
      }
    }

    // disable submit to prevent double submit
    submitBtn.disabled = true;
    submitBtn.textContent = '⏳ Mengunggah...';

    // allow normal form submission to server
    // if you want AJAX upload, replace the following with fetch/XHR logic
    return true;
  });

  // warn user if leaving during upload
  window.addEventListener('beforeunload', (ev) => {
    if (!submitBtn.disabled) return;
    const message = 'Upload sedang berlangsung. Yakin ingin meninggalkan halaman?';
    ev.returnValue = message;
    return message;
  });
});
