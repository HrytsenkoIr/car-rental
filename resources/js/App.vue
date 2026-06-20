<template>
    <div id="root">
        <header>
            <h1>Car Rental</h1>
        </header>
        <main>
            <FilterBar @filter="onFilter" />
            <div class="view-switcher" role="group" aria-label="Calendar view">
                <button
                    type="button"
                    :class="{ active: activeView === 'summary' }"
                    @click="activeView = 'summary'"
                >
                    Summary table
                </button>
                <button
                    type="button"
                    :class="{ active: activeView === 'calendar' }"
                    @click="activeView = 'calendar'"
                >
                    Car calendar
                </button>
            </div>
            <div v-if="loading" class="loading">Loading...</div>
            <div v-else-if="error" class="error">{{ error }}</div>
            <CalendarTable
                v-else-if="activeView === 'summary'"
                :cars="cars"
                :month="month"
                :year="year"
                :daysInMonth="daysInMonth"
            />
            <CarCalendar
                v-else
                :cars="cars"
                :month="month"
                :year="year"
                :daysInMonth="daysInMonth"
                :showHourly="showHourly"
                @filter="onFilter"
                @toggle-hourly="onToggleHourly"
            />
        </main>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';
import FilterBar from './components/FilterBar.vue';
import CalendarTable from './components/CalendarTable.vue';
import CarCalendar from './components/CarCalendar.vue';

const cars        = ref([]);
const month       = ref(null);
const year        = ref(null);
const daysInMonth = ref(0);
const loading     = ref(false);
const error       = ref(null);
const activeView  = ref('summary');
const showHourly  = ref(false);

async function onFilter({ year: y, month: m }) {
    loading.value = true;
    error.value   = null;
    try {
        const { data } = await axios.get('/api/calendar', {
            params: { year: y, month: m, show_hourly: showHourly.value }
        });
        cars.value        = data.cars;
        month.value       = data.month;
        year.value        = data.year;
        daysInMonth.value = data.days_in_month;
    } catch (e) {
        error.value = 'Error loading data: ' + (e.response?.data?.message ?? e.message);
    } finally {
        loading.value = false;
    }
}

function onToggleHourly(value) {
    showHourly.value = value;

    if (year.value && month.value) {
        onFilter({ year: year.value, month: month.value });
    }
}
</script>

<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'Segoe UI', Arial, sans-serif;
    background: #f0f2f5;
    color: #222;
}

#root {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

header {
    background: #1a1a2e;
    color: #fff;
    padding: 18px 32px;
    font-size: 1.4rem;
    letter-spacing: .5px;
}

main {
    padding: 24px 16px;
    flex: 1;
}

.loading {
    text-align: center;
    padding: 40px;
    font-size: 1.2rem;
    color: #666;
}

.error {
    color: #c0392b;
    background: #fdecea;
    border: 1px solid #e74c3c;
    border-radius: 6px;
    padding: 14px 20px;
    margin: 16px 0;
}

.view-switcher {
    display: inline-flex;
    border: 1px solid #ccd2dd;
    border-radius: 6px;
    overflow: hidden;
    background: #fff;
    margin-bottom: 16px;
}

.view-switcher button {
    border: 0;
    background: transparent;
    color: #4b5563;
    padding: 8px 14px;
    font-size: .92rem;
    font-weight: 600;
    cursor: pointer;
}

.view-switcher button + button {
    border-left: 1px solid #ccd2dd;
}

.view-switcher button.active {
    background: #1a1a2e;
    color: #fff;
}
</style>
