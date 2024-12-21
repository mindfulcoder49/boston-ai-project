<template>
    <div class="carousel-container" v-if="hasImages">
        <div class="carousel-wrapper" ref="carouselWrapper">
            <div
                v-for="(data, index) in filteredDataPoints"
                :key="index"
                :class="{ 'carousel-slide': true, 'active': isSlideActive(index) }"
                @click="onImageClick(data)"
            >
                <div class="carousel-slide-inner">
                    <img
                        v-if="data.info.closed_photo"
                        :src="data.info.closed_photo"
                        alt="Data Point Image"
                        class="carousel-image"
                    />
                    <img
                        v-else-if="data.info.submitted_photo"
                        :src="data.info.submitted_photo"
                        alt="Data Point Image"
                        class="carousel-image"
                    />
                    <div class="data-type-label">
                        {{ data.info.type }}
                    </div>
                </div>
            </div>
        </div>
        <div class="carousel-decoration">
            <div class="carousel-controls">
                <button @click="prevSlide" :disabled="currentIndex === 0" class="control-button prev-button">
                    <
                </button>
                <button @click="nextSlide" :disabled="isLastSlide" class="control-button next-button">
                    >
                </button>
            </div>

            <div class="carousel-indicators">
                <button
                    v-for="(data, index) in filteredDataPoints"
                    :key="index"
                    @click="goToSlideGroup(index)"
                    :class="{ 'indicator-button': true, 'active': isIndicatorActive(index) }"
                ></button>
            </div>
        </div>
    </div>
     <div v-else class="no-image-container">
       No Images Available
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
    dataPoints: {
        type: Array,
        default: () => [],
    },
});

const carouselWrapper = ref(null);
const currentIndex = ref(0); // Current slide index (group of 3)
const visibleSlides = 3;

const emit = defineEmits(['on-image-click']);

const hasImage = (data) => {
    return data.info?.closed_photo || data.info?.submitted_photo;
};

const onImageClick = (data) => {
    emit('on-image-click', data);
};

const filteredDataPoints = computed(() => {
    return props.dataPoints.filter((data) => hasImage(data));
});

const hasImages = computed(() => {
    return filteredDataPoints.value.length > 0;
});

const isLastSlide = computed(() => {
    return currentIndex.value >= Math.floor((filteredDataPoints.value.length -1) / visibleSlides);
});


const prevSlide = () => {
    if (currentIndex.value > 0) {
        currentIndex.value--;
         scrollToSlideGroup();

    }
};

const nextSlide = () => {
   if (!isLastSlide.value) {
       currentIndex.value++;
       scrollToSlideGroup();
   }
};

const goToSlideGroup = (index) => {
    currentIndex.value = Math.floor(index/visibleSlides);
    scrollToSlideGroup();
}

const isSlideActive = (index) => {
  const start = currentIndex.value * visibleSlides;
  return index >= start && index < start + visibleSlides;

};

const isIndicatorActive = (index) => {
    return Math.floor(index/visibleSlides) === currentIndex.value;
}

const scrollToSlideGroup = () => {
      if (carouselWrapper.value) {
            carouselWrapper.value.scrollTo({
                left: carouselWrapper.value.offsetWidth * currentIndex.value,
                behavior: 'smooth',
            });
      }
}
watch(
    () => currentIndex.value,
  () => {
        scrollToSlideGroup();
  });
</script>

<style scoped>
.carousel-container {
    position: relative;
    width: 100%;
}

.carousel-wrapper {
    display: flex;
    overflow-x: auto;
    scroll-snap-type: x mandatory;
    scrollbar-width: none; /* Hide scrollbar in Firefox */
    -ms-overflow-style: none; /* Hide scrollbar in IE and Edge */
    scroll-behavior: smooth;
}

.carousel-wrapper::-webkit-scrollbar {
    display: none; /* Hide scrollbar in Chrome, Safari, and Opera */
}


.carousel-slide {
    flex: 0 0 calc(100% / 3); /* Three slides per row */
    scroll-snap-align: start;
    padding: 10px;
    cursor: pointer;
     position: relative;
    height: auto;
     display: flex;
     align-items: center;
     justify-content: center;
  transition: opacity 0.3s ease;

}

.carousel-slide-inner {
     max-width: 100%;
    max-height: 100%;
    position: relative;
    display: flex;
     align-items: center;
     justify-content: center;

}

.carousel-slide.active {
    opacity: 1;
}

.carousel-image {
      width: 100%;
    height: 100%;
    object-fit: cover; /* or contain depending on desired look */
    border-radius: 8px;
     aspect-ratio: 1/1;
}

.data-type-label {
    position: absolute;
    top: 10px;
    left: 10px;
    background-color: rgba(0, 0, 0, 0.7);
    color: #fff;
    padding: 5px 8px;
    border-radius: 4px;
    font-size: 0.9rem;
}

.no-image-container {
  text-align: center;
    padding: 20px;
  font-style: italic;
  color: #aaa;
}

.carousel-decoration {
    position: absolute;
    bottom: 15px;
    text-align: center;
    width: 100%;
    
}

.carousel-controls {
    display: flex;
    justify-content: space-between;
    width: 100%;
    padding: 0 10px;
}

.control-button {
    background-color: #f0f0f0;
    border: 1px solid #555;
    padding: 8px 12px;
    margin: 0 5px;
    cursor: pointer;
    font-size: 1.2rem;
    border-radius: 4px;
}

.control-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.carousel-indicators {
    display: flex;
    justify-content: center;
    margin-top: 10px;
}

.indicator-button {
    background-color: #ddd;
    border: 1px solid black;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin: 0 5px;
    cursor: pointer;
}

.indicator-button.active {
    background-color: #555;
}
</style>