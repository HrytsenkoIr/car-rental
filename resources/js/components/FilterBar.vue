<template>
    <div class="filter-bar">
        <label>
            Month:
            <select v-model="selectedMonth">
                <option v-for="m in months" :key="m.value" :value="m.value">
                    {{ m.label }}
                </option>
            </select>
        </label>

        <label>
            Year:
            <select v-model="selectedYear">
                <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
            </select>
        </label>

        <button @click="apply">Show</button>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const emit = defineEmits(['filter']);

const now          = new Date();
const selectedMonth = ref(now.getMonth() + 1);
const selectedYear  = ref(now.getFullYear());

const months = [
    { value: 1,  label: 'January'   },
    { value: 2,  label: 'February'  },
    { value: 3,  label: 'March'     },
    { value: 4,  label: 'April'     },
    { value: 5,  label: 'May'       },
    { value: 6,  label: 'June'      },
    { value: 7,  label: 'July'      },
    { value: 8,  label: 'August'    },
    { value: 9,  label: 'September' },
    { value: 10, label: 'October'   },
    { value: 11, label: 'November'  },
    { value: 12, label: 'December'  },
];

// Генеруємо роки: від 2020 до поточного
const currentYear = now.getFullYear();
const years = Array.from({ length: currentYear - 2019 }, (_, i) => 2020 + i).reverse();

function apply() {
    emit('filter', { year: selectedYear.value, month: selectedMonth.value });
}
</script>

<style scoped>
.filter-bar {
    display: flex;
    gap: 16px;
    align-items: center;
    background: #fff;
    border-radius: 8px;
    padding: 14px 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,.08);
    flex-wrap: wrap;
}

label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    font-size: .95rem;
}

select {
    border: 1px solid #ccc;
    border-radius: 4px;
    padding: 6px 10px;
    font-size: .95rem;
    cursor: pointer;
}

button {
    background: #1a1a2e;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 8px 24px;
    font-size: .95rem;
    font-weight: 600;
    cursor: pointer;
    transition: background .2s;
}

button:hover {
    background: #16213e;
}
</style>
