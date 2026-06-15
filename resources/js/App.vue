<template>
    <div class="min-h-screen">
        <Home v-if="!activePresentationId" @present="startPresenting" />
        <Presenter
            v-else
            :presentation-id="activePresentationId"
            @exit="exitPresenting"
        />
    </div>
</template>

<script setup>
import { ref } from 'vue';
import Home from './components/Home.vue';
import Presenter from './components/Presenter.vue';

const activePresentationId = ref(null);

function startPresenting(id) {
    activePresentationId.value = id;
}

function exitPresenting() {
    activePresentationId.value = null;
}
</script>
