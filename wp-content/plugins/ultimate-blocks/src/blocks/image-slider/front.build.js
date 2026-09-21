"use strict";

Array.prototype.slice.call(document.getElementsByClassName("ub_image_slider")).forEach(function (instance) {
  var _instance$getElements, _instance$getElements2;
  // Find the swiper container - it might be the instance itself or a child .swiper-container
  var swiperContainer = instance.classList.contains("swiper-container") ? instance : instance.querySelector(".swiper-container");
  if (!swiperContainer || !swiperContainer.dataset.swiperData) {
    return;
  }
  var swiper = new Swiper(swiperContainer.id ? "#".concat(swiperContainer.id) : swiperContainer, JSON.parse(swiperContainer.dataset.swiperData));
  instance === null || instance === void 0 || (_instance$getElements = instance.getElementsByClassName("swiper-button-next")[0]) === null || _instance$getElements === void 0 || _instance$getElements.addEventListener("keydown", function (e) {
    if (e.key === " ") {
      e.preventDefault();
    }
  });
  instance === null || instance === void 0 || (_instance$getElements2 = instance.getElementsByClassName("swiper-button-prev")[0]) === null || _instance$getElements2 === void 0 || _instance$getElements2.addEventListener("keydown", function (e) {
    if (e.key === " ") {
      e.preventDefault();
    }
  });
  Array.prototype.slice.call(instance.getElementsByClassName("swiper-pagination-bullet")).forEach(function (bullet) {
    bullet.addEventListener("keydown", function (e) {
      if (e.key === " ") {
        e.preventDefault();
      }
    });
  });
});