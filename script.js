

// === Slideshow ===
let slideIndex = 0;
showSlides();

function showSlides() {
  let i;
  let slides = document.getElementsByClassName("slides");
  let dots = document.getElementsByClassName("dot");

  for (i = 0; i < slides.length; i++) {
    slides[i].style.display = "none";  
  }

  slideIndex++;
  if (slideIndex > slides.length) {slideIndex = 1}    

  for (i = 0; i < dots.length; i++) {
    dots[i].className = dots[i].className.replace(" active", "");
  }

  if (slides.length > 0) {
    slides[slideIndex-1].style.display = "block";  
    if (dots.length >= slideIndex) {
      dots[slideIndex-1].className += " active";
    }
  }

  setTimeout(showSlides, 3000);
}

// === Sidebar Hamburger ===
function toggleSidebar() {
  const sidebar = document.getElementById("sidebar");
  sidebar.classList.toggle("active");
}