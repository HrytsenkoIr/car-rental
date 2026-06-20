<template>
    <div class="table-wrapper">
        <h2 class="period-title">{{ monthName }} {{ year }}</h2>

        <div v-if="!cars.length" class="no-data">
            Select month and year, then press "Show"
        </div>

        <table v-else>
            <thead>
            <tr>
                <th>ID</th>
                <th>Car</th>
                <th>Year</th>
                <th>Plate</th>
                <th>Color</th>
                <th>Type</th>
                <th>Body</th>
                <th class="col-num">Free</th>
                <th class="col-num">Service</th>
                <th class="col-num">Busy</th>
                <th class="col-num">All</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="car in cars" :key="car.id">
                <td>{{ car.id }}</td>
                <td class="car-name">{{ car.name }}</td>
                <td>{{ car.year }}</td>
                <td>{{ car.number }}</td>
                <td>{{ car.color }}</td>
                <td><span class="type-pill">{{ car.type }}</span></td>
                <td>{{ car.body_type }}</td>
                <td class="col-num"><span class="badge badge-free">{{ car.free }}</span></td>
                <td class="col-num"><span class="badge badge-service">{{ car.service }}</span></td>
                <td class="col-num"><span class="badge badge-busy">{{ car.busy }}</span></td>
                <td class="col-num">{{ car.all }}</td>
            </tr>
            </tbody>
            <tfoot>
            <tr>
                <td colspan="7"><strong>Total: {{ cars.length }} cars</strong></td>
                <td class="col-num"><strong>{{ totalFree }}</strong></td>
                <td class="col-num"><strong>{{ totalService }}</strong></td>
                <td class="col-num"><strong>{{ totalBusy }}</strong></td>
                <td class="col-num"></td>
            </tr>
            </tfoot>
        </table>

        <div v-if="cars.length" class="legend">
            <span class="badge badge-free">N</span> Free - 9+ free hours in 09:00–21:00
            &nbsp;&nbsp;
            <span class="badge badge-service">N</span> Service - car maintenance
            &nbsp;&nbsp;
            <span class="badge badge-busy">N</span> Busy - no 9-hour free window
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    cars:        { type: Array,  default: () => [] },
    month:       { type: Number, default: null },
    year:        { type: Number, default: null },
    daysInMonth: { type: Number, default: 0 },
});

const MONTHS = [
    '', 'January', 'February', 'March', 'April',
    'May', 'June', 'July', 'August', 'September',
    'October', 'November', 'December',
];

const monthName    = computed(() => props.month ? MONTHS[props.month] : '');
const totalFree    = computed(() => props.cars.reduce((s, c) => s + c.free,    0));
const totalService = computed(() => props.cars.reduce((s, c) => s + c.service, 0));
const totalBusy    = computed(() => props.cars.reduce((s, c) => s + c.busy,    0));
</script>

<style scoped>
.table-wrapper {
    background: #fff;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 12px rgba(0,0,0,.08);
    overflow-x: auto;
}
.period-title {
    font-size: 1.3rem;
    margin-bottom: 16px;
    color: #1a1a2e;
}
.no-data {
    color: #888;
    text-align: center;
    padding: 40px;
    font-size: 1.05rem;
}
table {
    width: 100%;
    border-collapse: collapse;
    font-size: .9rem;
}
th, td {
    padding: 10px 12px;
    text-align: left;
    border-bottom: 1px solid #eee;
}
th {
    background: #1a1a2e;
    color: #fff;
    font-weight: 600;
    white-space: nowrap;
}
tr:hover td { background: #f8f9ff; }
tfoot td {
    border-top: 2px solid #ddd;
    background: #f5f5f5;
}
.car-name {
    max-width: 260px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.col-num { text-align: center; }
.type-pill {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 999px;
    background: #eef2f7;
    color: #344054;
    font-size: .8rem;
    font-weight: 700;
    text-transform: capitalize;
}
.badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 12px;
    font-weight: 700;
    font-size: .85rem;
    min-width: 32px;
    text-align: center;
}
.badge-free    { background: #d4edda; color: #155724; }
.badge-service { background: #fff3cd; color: #856404; }
.badge-busy    { background: #f8d7da; color: #721c24; }
.legend {
    margin-top: 16px;
    font-size: .85rem;
    color: #555;
}
</style>
