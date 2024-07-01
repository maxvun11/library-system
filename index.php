<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Book Exchange</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <?php include 'header.php'; ?>

  <?php include 'nav.php'; ?>
  <div class="display">
    <img class="slideshow fade" src='homepage-slideshow/homepage-slideshow.jpg' style="width:100%">
    <img class="slideshow fade" src="homepage-slideshow/homepage-slideshow2.jpg" style="width:100%">
    <img class="slideshow fade" src="homepage-slideshow/homepage-slideshow3.jpg" style="width:100%">
    <img class="slideshow fade" src="homepage-slideshow/homepage-slideshow4.jpg" style="width:100%">
    
      <div class="dotters" style="text-align:center">
      <span class="dot"></span>
      <span class="dot"></span>
      <span class="dot"></span>
      <span class="dot"></span>
    </div>
  </div>

<script>
var slideIndex = 0;
showSlides();

function showSlides() {
  var i;
  var slides = document.getElementsByClassName("slideshow");
  var dots = document.getElementsByClassName("dot");
  for (i = 0; i < slides.length; i++) {
    slides[i].style.display = "none";
  }
  slideIndex++;
  if (slideIndex > slides.length) {slideIndex = 1}
  for (i = 0; i < dots.length; i++) {
    dots[i].className = dots[i].className.replace(" active", "");
  }
  slides[slideIndex-1].style.display = "block";
  dots[slideIndex-1].className += " active";
  setTimeout(showSlides, 2000); // Change image every 2 seconds
}
</script>

</body>
</html>

  <section class="hero">
    <h2>Trade Your Books Easily!</h2>
    <p>Find the books you want and exchange them with others.</p>
  </section>

  <?php include 'footer.php'; ?>
</body>
</html>