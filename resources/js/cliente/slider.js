import Swiper, { Navigation, Pagination, Scrollbar } from 'swiper';
// Now you can use Swiper

// Now you can use Swiper
const swiper = new Swiper('.swiper-container', {
  // Install modules
    modules: [Navigation, Pagination, Scrollbar],
     speed: 500,

    // If we need pagination
    pagination: {
        el: '.swiper-pagination',
    },

    // Navigation arrows
    navigation: {
        nextEl: '.swiper-button-next',
        prevEl: '.swiper-button-prev',
    },

    // And if we need scrollbar
    scrollbar: {
        el: '.swiper-scrollbar',
    },
  // ...
});
 