<template>
    <div class="min-h-screen">
        <Home
            v-if="currentView === 'home'"
            @present="startPresenting"
            @open-notes="openNotesAssistant"
        />
        <Presenter
            v-else-if="currentView === 'presenter'"
            :presentation-id="activePresentationId"
            @exit="exitPresenting"
        />
        <NotesAssistant
            v-else-if="currentView === 'notes'"
            @exit="exitNotesAssistant"
        />
    </div>
</template>

<script setup>
import { ref } from 'vue';
import Home from './components/Home.vue';
import Presenter from './components/Presenter.vue';
import NotesAssistant from './components/NotesAssistant.vue';

const currentView = ref('home');
const activePresentationId = ref(null);

function startPresenting(id) {
    activePresentationId.value = id;
    currentView.value = 'presenter';
}

function exitPresenting() {
    activePresentationId.value = null;
    currentView.value = 'home';
}

function openNotesAssistant() {
    currentView.value = 'notes';
}

function exitNotesAssistant() {
    currentView.value = 'home';
}
</script>
