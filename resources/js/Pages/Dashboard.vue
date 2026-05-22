<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Welcome from '@/Components/Welcome.vue';
import { onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const page = usePage();

onMounted(() => {
    // Assumindo que o ID do usuário logado está disponível globalmente
    const userId = page.props.auth.user.id;

    window.Echo.private(`user.${userId}`)
        .listen('.socket.test', (e) => {
            console.log('Novo evento chegou:', e.user);
            alert(`Usuário ${e.user.id} chegou!`);
        });
});

const testSocket = () => {
    const userId = page.props.auth.user.id;
    axios.post( route('test-event', { user: userId }) )
        .then(response => {
            console.log(response.data);
        })
        .catch(error => {
            console.error('Erro ao enviar evento:', error);
        });
};
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <PrimaryButton @click="testSocket">Testar Socket</PrimaryButton>
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                    <Welcome />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
