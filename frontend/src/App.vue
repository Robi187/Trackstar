<script setup>
import { ref } from 'vue'
import axios from 'axios'

const response = ref('Noch nichts geladen')

async function testBackend() {
  try {
    const res = await axios.get('https://localhost/api/test')
    response.value = res.data.message
  } catch (error) {
    console.error('Backend Fehler:', error)
    throw new Error('Fehler bei Verbindung!')
  }
}
</script>

<template>
  <h1>Vue Frontend</h1>

  <button @click="testBackend()">
    Backend testen
  </button>

  <p>{{ response }}</p>
</template>