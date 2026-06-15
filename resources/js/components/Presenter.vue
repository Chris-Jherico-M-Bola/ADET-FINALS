<template>
    <PresentationViewer :presentation-id="presentationId" @exit="$emit('exit')" />
</template>

<script setup>
import PresentationViewer from './PresentationViewer.vue';

defineProps({
    presentationId: {
        type: String,
        required: true,
    },
});

defineEmits(['exit']);
</script>
