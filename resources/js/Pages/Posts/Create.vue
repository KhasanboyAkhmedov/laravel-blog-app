<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';

const form = useForm({
    title: '',
    content: '',
});

const submit = () => {
    form.post(route('posts.store'));
};
</script>

<template>
    <AdminLayout>
        <template #header>New Post</template>

        <div class="max-w-2xl bg-white rounded shadow p-6">
            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <InputLabel for="title" value="Title" />
                    <TextInput
                        id="title"
                        v-model="form.title"
                        type="text"
                        class="mt-1 block w-full"
                        autofocus
                    />
                    <InputError :message="form.errors.title" class="mt-1" />
                </div>

                <div>
                    <InputLabel for="content" value="Content" />
                    <textarea
                        id="content"
                        v-model="form.content"
                        rows="8"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm"
                    />
                    <InputError :message="form.errors.content" class="mt-1" />
                </div>

                <PrimaryButton :disabled="form.processing">
                    Publish Post
                </PrimaryButton>
            </form>
        </div>
    </AdminLayout>
</template>
