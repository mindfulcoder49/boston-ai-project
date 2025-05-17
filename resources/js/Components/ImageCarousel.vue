<template>
    <div class="carousel-container" :style="{ height: carouselHeight }" v-if="hasImages">
        <div class="carousel-wrapper" ref="carouselWrapper">
            <div
                v-for="(data, index) in filteredDataPoints"
                :key="data.id || index" 
                class="carousel-slide"
                :style="{ flexBasis: `calc(100% / ${currentVisibleSlides})` }"
                @click="onImageClick(data)"
            >
                <div class="carousel-slide-inner">
                    <img
                        v-if="data.closed_photo"
                        :src="data.closed_photo"
                        alt="Data Point Image"
                        class="carousel-image"
                    />
                    <img
                        v-else-if="data.submitted_photo"
                        :src="data.submitted_photo"
                        alt="Data Point Image"
                        class="carousel-image"
                    />
                    
                    <div v-if="data.type" class="data-type-label">
                        {{ data.type }}
                    </div>
                </div>
            </div>
        </div>
        <div class="carousel-decoration" v-if="totalPages > 1">
            <div class="carousel-controls">
                <button @click="prevPage" :disabled="currentPageIndex === 0" class="control-button prev-button">
                    <
                </button>
                <button @click="nextPage" :disabled="isLastPage" class="control-button next-button">
                    >
                </button>
            </div>

            <div class="carousel-indicators">
                <button
                    v-for="page in totalPages"
                    :key="`indicator-${page-1}`"
                    @click="goToPage(page - 1)"
                    :class="{ 'indicator-button': true, 'active': (page - 1) === currentPageIndex }"
                ></button>
            </div>
        </div>
    </div>
    <div v-else class="no-image-container" :style="{ height: carouselHeight }">
       No Images Available
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    dataPoints: {
        type: Array,
        default: () => [],
    },
    carouselHeight: {
        type: String,
        default: '300px', // Default height, can be overridden by prop
    },
    // Define breakpoints and corresponding slides per view
    // Example: { 1024: 4, 768: 3, 600: 2, 0: 1 } (screenWidth: slides)
    // Order from largest to smallest screen width
    responsiveBreakpoints: {
        type: Object,
        default: () => ({
            1200: 5, // Large desktops: 4 slides
            992: 4,  // Desktops: 3 slides
            768: 3,  // Tablets: 2 slides
            0: 2     // Mobile: 1 slide (fallback)
        })
    }
});

const emit = defineEmits(['on-image-click']);

const carouselWrapper = ref(null);
const currentPageIndex = ref(0); // Current page index
const currentVisibleSlides = ref(props.responsiveBreakpoints[0] || 1); // Number of slides visible at a time

const hasImage = (data) => {
    return data?.closed_photo || data?.submitted_photo;
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

const totalPages = computed(() => {
    if (!filteredDataPoints.value.length || currentVisibleSlides.value === 0) return 0;
    return Math.ceil(filteredDataPoints.value.length / currentVisibleSlides.value);
});

const isLastPage = computed(() => {
    if (totalPages.value === 0) return true;
    return currentPageIndex.value >= totalPages.value - 1;
});

const prevPage = () => {
    if (currentPageIndex.value > 0) {
        currentPageIndex.value--;
    }
};

const nextPage = () => {
   if (!isLastPage.value) {
       currentPageIndex.value++;
   }
};

const goToPage = (pageIndex) => {
  if (pageIndex >= 0 && pageIndex < totalPages.value) {
      currentPageIndex.value = pageIndex;
  }
};

const scrollToCurrentPage = () => {
    if (carouselWrapper.value && carouselWrapper.value.offsetWidth) {
        // The amount to scroll is the width of the wrapper (which shows one page)
        // multiplied by the current page index.
        const scrollAmount = carouselWrapper.value.offsetWidth * currentPageIndex.value;
        carouselWrapper.value.scrollTo({
            left: scrollAmount,
            behavior: 'smooth',
        });
    }
};

const updateVisibleSlides = () => {
    const screenWidth = window.innerWidth;
    let slides = props.responsiveBreakpoints[0] || 1; // Default to smallest or first defined

    // Iterate through sorted breakpoints (largest to smallest screen width)
    const sortedBreakpoints = Object.entries(props.responsiveBreakpoints)
                                   .map(([width, count]) => [parseInt(width), count])
                                   .sort((a, b) => b[0] - a[0]); // Sort descending by width

    for (const [breakpointWidth, count] of sortedBreakpoints) {
        if (screenWidth >= breakpointWidth) {
            slides = count;
            break;
        }
    }
    // If no breakpoint matches (e.g., screenWidth is smaller than all defined min widths)
    // use the count for the smallest breakpoint (last one in sorted list if not already caught)
    if (slides === (props.responsiveBreakpoints[0] || 1) && sortedBreakpoints.length > 0) {
         const smallestBreakpoint = sortedBreakpoints[sortedBreakpoints.length - 1];
         if (screenWidth < smallestBreakpoint[0]) {
            slides = smallestBreakpoint[1];
         }
    }

    if (currentVisibleSlides.value !== slides) {
        currentVisibleSlides.value = slides;
        // Reset to first page if number of visible slides changes,
        // to avoid being on an out-of-bounds page.
        // Or, try to maintain the current first visible item
        const firstItemIndexOfCurrentPage = currentPageIndex.value * currentVisibleSlides.value;
        currentPageIndex.value = Math.floor(firstItemIndexOfCurrentPage / slides) || 0;

        // Ensure currentPageIndex is not out of bounds after recalculating
        if(totalPages.value > 0) {
           currentPageIndex.value = Math.min(currentPageIndex.value, totalPages.value - 1);
        } else {
           currentPageIndex.value = 0;
        }
    }
};


onMounted(() => {
    updateVisibleSlides(); // Initial check
    window.addEventListener('resize', updateVisibleSlides);
    // Ensure initial scroll position is correct if not starting at index 0
    // Needs a slight delay for DOM to be ready if currentVisibleSlides was calculated
    // and might have caused a re-render
    setTimeout(scrollToCurrentPage, 0);
});

onUnmounted(() => {
    window.removeEventListener('resize', updateVisibleSlides);
});

watch(currentPageIndex, () => {
    scrollToCurrentPage();
});

// Watch for changes in data points or visible slides, as they affect totalPages
watch([filteredDataPoints, currentVisibleSlides], () => {
    if (totalPages.value > 0 && currentPageIndex.value >= totalPages.value) {
        currentPageIndex.value = Math.max(0, totalPages.value - 1);
    } else if (totalPages.value === 0) {
        currentPageIndex.value = 0;
    }
    // This might be needed if data changes and current page becomes empty or invalid
    // setTimeout(scrollToCurrentPage, 0); // scroll after DOM updates
}, { deep: true });


// This computed property is not directly used for display but can be helpful for debugging
// or if you want to apply a style to specifically visible slides.
const currentlyVisibleItemIndexes = computed(() => {
    const start = currentPageIndex.value * currentVisibleSlides.value;
    const end = start + currentVisibleSlides.value;
    return filteredDataPoints.value.map((_, index) => index).slice(start, end);
});

</script>

<style scoped>
.carousel-container {
    position: relative;
    width: 100%;
    /* height: 300px; Set by prop carouselHeight */
    overflow: hidden; /* Crucial to contain slides within the fixed height */
}

.carousel-wrapper {
    display: flex;
    height: 100%; /* Fill the container's height */
    overflow-x: auto; /* Allows manual scroll if needed, but primarily for scrollTo */
    scroll-snap-type: x mandatory; /* Each "page" will snap */
    scrollbar-width: none; /* Hide scrollbar in Firefox */
    -ms-overflow-style: none; /* Hide scrollbar in IE and Edge */
    scroll-behavior: smooth; /* Used by scrollTo JS, not direct user scroll */
}

.carousel-wrapper::-webkit-scrollbar {
    display: none; /* Hide scrollbar in Chrome, Safari, and Opera */
}

.carousel-slide {
    /* flex-basis set dynamically by :style */
    flex-shrink: 0; /* Prevent slides from shrinking */
    height: 100%; /* Make slide take full height of wrapper */
    scroll-snap-align: start; /* Each slide group aligns to the start */
    cursor: pointer;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box; /* Include padding/border in element's total width/height */
    padding: 5px; /* Optional: adds some spacing around images */
}

.carousel-slide-inner {
    width: 100%;
    height: 100%;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden; /* Ensure image doesn't overflow inner container */
}

.carousel-image {
    width: 100%;
    height: 100%;
    object-fit: cover; /* Cover the area, cropping if necessary */
    /* aspect-ratio: 1/1; */ /* Remove if you want images to fill slide space non-uniformly,
                                or adjust as needed. 'cover' will maintain aspect ratio. */
}

.data-type-label {
    position: absolute;
    top: 10px;
    left: 10px;
    background-color: rgba(0, 0, 0, 0.7);
    color: #fff;
    padding: 3px 6px;
    border-radius: 4px;
    font-size: 0.8rem;
    z-index: 1;
}

.no-image-container {
    display: flex;
    align-items: center;
    justify-content: center;
    /* height: 300px; Set by prop carouselHeight */
    text-align: center;
    font-style: italic;
    color: #aaa;
    border: 1px dashed #ccc;
    box-sizing: border-box;
}

.carousel-decoration {
    position: absolute;
    bottom: 10px; /* Adjusted for better aesthetics */
    left: 0;
    right: 0;
    pointer-events: none;
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.carousel-controls {
    display: flex;
    justify-content: center;
    margin-bottom: 8px; /* Space between controls and indicators */
}

.control-button {
    background-color: rgba(240, 240, 240, 0.8); /* Semi-transparent */
    border: 1px solid #555;
    padding: 6px 10px; /* Slightly smaller */
    margin: 0 5px;
    cursor: pointer;
    font-size: 1rem;
    border-radius: 4px;
    pointer-events: auto;
    transition: background-color 0.2s;
}
.control-button:hover:not(:disabled) {
    background-color: rgba(220, 220, 220, 0.9);
}

.control-button:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.carousel-indicators {
    display: flex;
    justify-content: center;
    pointer-events: auto; /* Allow clicks on indicators */
}

.indicator-button {
    background-color: rgba(221, 221, 221, 0.7); /* Semi-transparent */
    border: 1px solid #555;
    width: 8px; /* Slightly smaller */
    height: 8px;
    border-radius: 50%;
    margin: 0 4px;
    cursor: pointer;
    padding: 0;
    transition: background-color 0.2s;
}
.indicator-button:hover:not(.active) {
    background-color: rgba(180, 180, 180, 0.8);
}

.indicator-button.active {
    background-color: #555;
}

/* No specific media queries needed here for slide counts as JS handles it,
   but you can add them for styling adjustments if necessary. */
@media screen and (max-width: 600px) {
    .data-type-label {
        font-size: .7rem;
        padding: 2px 4px;
        top: 5px;
        left: 5px;
    }
    .control-button {
        padding: 4px 8px;
        font-size: 0.9rem;
    }
     .indicator-button {
        width: 7px;
        height: 7px;
    }
}
</style>