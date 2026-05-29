
function validateForm() {
  let nama = document.getElementById("nama").value;
  let harga = document.getElementById("harga").value;
  let deskripsi = document.getElementById("deskripsi").value;
  let gambar = document.getElementById("gambar").value;

  if (nama === "" || harga === "" || deskripsi === "" || gambar === "") {
    alert("Semua field wajib diisi!");
    return false;
  }
  return true;
}



