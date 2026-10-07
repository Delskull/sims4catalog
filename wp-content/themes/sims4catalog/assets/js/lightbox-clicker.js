    document.querySelectorAll('.js-open-lightbox').forEach(button => {
    button.addEventListener('click', function (e) {
        e.preventDefault();
        const slideIndex = parseInt(this.getAttribute('data-bs-slide-to'), 10);
        const modalElement = document.getElementById('imageLightbox');
        const carouselElement = document.getElementById('lightboxCarousel');
        if (modalElement && carouselElement) {
            const myCarousel = bootstrap.Carousel.getOrCreateInstance(carouselElement);
            myCarousel.to(slideIndex);
            const myModal = bootstrap.Modal.getOrCreateInstance(modalElement);
            myModal.show();
        }
    });
});