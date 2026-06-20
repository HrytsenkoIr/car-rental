<template>
    <div class="calendar-container">
        <div class="top-panel">
            <div class="date-navigator">
                <button @click="prevMonth" class="nav-arrow">&lt;</button>
                <span class="current-date">{{ formattedCurrentDate }}</span>
                <button @click="nextMonth" class="nav-arrow">&gt;</button>
            </div>

            <label class="toggle-label">
                <input
                    type="checkbox"
                    :checked="props.showHourly"
                    @change="toggleHourly"
                />
                <span>Show hourly offers</span>
            </label>
        </div>

        <div class="calendar-tools">
            <input
                v-model="searchQuery"
                class="search-input"
                type="search"
                placeholder="Search car or plate"
            />
            <select v-model="statusFilter" class="status-select">
                <option value="all">All cars</option>
                <option value="rented">Has rentals</option>
                <option value="service">Has service</option>
                <option value="hourly">Has hourly offers</option>
                <option value="free">Fully free</option>
            </select>
            <button type="button" class="export-btn" @click="exportVisibleCars">
                Export CSV
            </button>
            <span class="result-count">{{ displayedCars.length }} cars</span>
        </div>

        <div class="legend-strip">
            <span><i class="legend-dot rented-dot"></i>Rented</span>
            <span><i class="legend-dot service-dot"></i>Service</span>
            <span><i class="legend-dot hourly-dot"></i>Hourly offer</span>
        </div>

        <div v-if="props.showHourly" class="hourly-panel">
            <div class="hourly-panel-head">
                <strong>Hourly offers</strong>
                <span>{{ hourlyOffers.length }} offers in this period</span>
            </div>
            <div v-if="hourlyOffers.length" class="hourly-list">
                <div v-for="offer in hourlyOffers" :key="offer.key" class="hourly-list-item">
                    <span class="hourly-car">{{ offer.carName }}</span>
                    <span class="hourly-plate">{{ offer.plate }}</span>
                    <span class="hourly-day">{{ getDayOfWeekName(offer.day) }} {{ offer.day }}</span>
                    <span class="hourly-time">{{ offer.label }}</span>
                </div>
            </div>
            <div v-else class="hourly-empty">No hourly offers for this period.</div>
        </div>

        <div v-if="!displayedCars.length" class="no-data">
            Select month and year, then press "Show"
        </div>

        <div v-else class="table-responsive">
            <table class="availability-table">
                <thead>
                <tr>
                    <th class="car-info-th">Car</th>
                    <th v-for="day in daysInMonthCount" :key="day" class="day-th">
                        <div class="day-wrapper">
                            <span class="day-name">{{ getDayOfWeekName(day) }}</span>
                            <span class="day-number">{{ day }}</span>
                        </div>
                    </th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="car in displayedCars" :key="car.id">
                    <td class="car-info-td">
                        <div class="car-meta">
                            <button type="button" class="car-title-button" @click="selectedCar = car">
                                {{ car.name }}
                            </button>
                            <button type="button" class="details-btn" @click="selectedCar = car">
                                Details
                            </button>
                        </div>
                    </td>

                    <td
                        v-for="day in daysInMonthCount"
                        :key="day"
                        class="day-cell"
                        :class="dayStatusClass(car, day)"
                        :title="dayTitle(car, day)"
                    >
                        <span
                            v-if="rentalSegmentStartingAt(car, day)"
                            class="rental-block"
                            :style="{ width: rentalBlockWidth(car, day) }"
                        >
                            {{ rentalBlockLabel(car, day) }}
                        </span>
                        <span
                            v-if="serviceSegmentStartingAt(car, day)"
                            class="service-block"
                            :style="{ width: serviceBlockWidth(car, day) }"
                        >
                            {{ serviceBlockLabel(car, day) }}
                        </span>
                        <span
                            v-if="hasHourlyOffer(car, day)"
                            class="hourly-indicator"
                            :title="hourlyTitle(car, day)"
                        ></span>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <div v-if="selectedCar" class="modal-backdrop" @click.self="selectedCar = null">
            <div class="car-modal" role="dialog" aria-modal="true">
                <div class="modal-head">
                    <div>
                        <h3>{{ selectedCar.name }}</h3>
                        <p>{{ selectedCar.number }}</p>
                    </div>
                    <button type="button" class="modal-close" @click="selectedCar = null">Close</button>
                </div>

                <dl class="car-detail-grid">
                    <div>
                        <dt>ID</dt>
                        <dd>{{ selectedCar.id }}</dd>
                    </div>
                    <div>
                        <dt>Year</dt>
                        <dd>{{ selectedCar.year }}</dd>
                    </div>
                    <div>
                        <dt>Color</dt>
                        <dd>{{ selectedCar.color }}</dd>
                    </div>
                    <div>
                        <dt>Type</dt>
                        <dd>{{ selectedCar.type }}</dd>
                    </div>
                    <div>
                        <dt>Body</dt>
                        <dd>{{ selectedCar.body_type }}</dd>
                    </div>
                    <div>
                        <dt>Plate</dt>
                        <dd>{{ selectedCar.number }}</dd>
                    </div>
                    <div>
                        <dt>Free</dt>
                        <dd>{{ selectedCar.free }}</dd>
                    </div>
                    <div>
                        <dt>Rented</dt>
                        <dd>{{ selectedCar.busy }}</dd>
                    </div>
                    <div>
                        <dt>Service</dt>
                        <dd>{{ selectedCar.service }}</dd>
                    </div>
                    <div>
                        <dt>All</dt>
                        <dd>{{ selectedCar.all }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    cars:        { type: Array,  default: () => [] },
    month:       { type: Number, default: null },
    year:        { type: Number, default: null },
    daysInMonth: { type: Number, default: 0 },
    showHourly:  { type: Boolean, default: false },
});

const emit = defineEmits(['filter', 'toggle-hourly']);

const searchQuery = ref('');
const statusFilter = ref('all');
const selectedCar = ref(null);

const currentYear = computed(() => props.year || new Date().getFullYear());
const currentMonth = computed(() => props.month || new Date().getMonth() + 1);

const daysInMonthCount = computed(() => {
    return props.daysInMonth || new Date(currentYear.value, currentMonth.value, 0).getDate();
});

const formattedCurrentDate = computed(() => {
    const date = new Date(currentYear.value, currentMonth.value - 1, 1);
    const options = { day: 'numeric', month: 'short', year: 'numeric' };
    const startStr = date.toLocaleDateString('en-US', options);

    const endDate = new Date(currentYear.value, currentMonth.value, 0);
    const endStr = endDate.toLocaleDateString('en-US', options);

    return `${startStr} - ${endStr}`;
});

const getDayOfWeekName = (day) => {
    const date = new Date(currentYear.value, currentMonth.value - 1, day);
    const days = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];
    return days[date.getDay()];
};

const getDayStatus = (car, day) => {
    return car.days?.[day - 1]?.status || 'free';
};

const isRented = (car, day) => getDayStatus(car, day) === 'rented';
const isService = (car, day) => getDayStatus(car, day) === 'service';

const rentalSegmentStartingAt = (car, day) => {
    return isRented(car, day) && !isRented(car, day - 1);
};

const serviceSegmentStartingAt = (car, day) => {
    return isService(car, day) && !isService(car, day - 1);
};

const segmentLength = (car, startDay, status) => {
    let length = 0;

    for (let day = startDay; day <= daysInMonthCount.value; day++) {
        if (getDayStatus(car, day) !== status) {
            break;
        }

        length++;
    }

    return length;
};

const rentalSegmentLength = (car, startDay) => segmentLength(car, startDay, 'rented');
const serviceSegmentLength = (car, startDay) => segmentLength(car, startDay, 'service');

const rentalBlockWidth = (car, day) => {
    return `calc(${rentalSegmentLength(car, day)} * var(--day-width) - 4px)`;
};

const serviceBlockWidth = (car, day) => {
    return `calc(${serviceSegmentLength(car, day)} * var(--day-width) - 4px)`;
};

const rentalBlockLabel = (car, day) => {
    const length = rentalSegmentLength(car, day);

    if (length <= 2) {
        return `${length}d`;
    }

    return `Rented for ${length} ${length === 1 ? 'day' : 'days'}`;
};

const serviceBlockLabel = (car, day) => {
    const length = serviceSegmentLength(car, day);

    if (length <= 2) {
        return `Svc ${length}d`;
    }

    return `Service ${length}d`;
};

const dayStatusClass = (car, day) => {
    return `day-${getDayStatus(car, day)}`;
};

const dayTitle = (car, day) => {
    const status = getDayStatus(car, day);

    if (status === 'rented') {
        return `${car.name} rented on day ${day}`;
    }

    if (status === 'service') {
        return `${car.name} in service on day ${day}`;
    }

    return `${car.name} free on day ${day}`;
};

const hourlyOffersForDay = (car, day) => {
    if (!props.showHourly) {
        return [];
    }

    return car.hourly_offers?.filter((offer) => offer.day === day) || [];
};

const hasHourlyOffer = (car, day) => hourlyOffersForDay(car, day).length > 0;

const hourlyTitle = (car, day) => {
    return hourlyOffersForDay(car, day)
        .map((offer) => `Hourly offer ${offer.label}`)
        .join('\n');
};

const hourlyOffers = computed(() => {
    return props.cars.flatMap((car) => {
        return (car.hourly_offers || []).map((offer) => ({
            ...offer,
            key: `${car.id}-${offer.booking_id}-${offer.day}`,
            carName: car.name,
            plate: car.number,
        }));
    }).sort((a, b) => a.day - b.day || a.start.localeCompare(b.start));
});

const displayedCars = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();

    return props.cars.filter((car) => {
        const matchesQuery = !query
            || car.name.toLowerCase().includes(query)
            || String(car.number).toLowerCase().includes(query)
            || String(car.color).toLowerCase().includes(query)
            || String(car.type).toLowerCase().includes(query);

        if (!matchesQuery) {
            return false;
        }

        if (statusFilter.value === 'rented') {
            return car.busy > 0;
        }

        if (statusFilter.value === 'service') {
            return car.service > 0;
        }

        if (statusFilter.value === 'hourly') {
            return (car.hourly_offers || []).length > 0;
        }

        if (statusFilter.value === 'free') {
            return car.busy === 0 && car.service === 0;
        }

        return true;
    });
});

const exportVisibleCars = () => {
    const headers = ['ID', 'Car', 'Plate', 'Year', 'Color', 'Type', 'Body', 'Free', 'Rented', 'Service', 'All'];
    const rows = displayedCars.value.map((car) => [
        car.id,
        car.name,
        car.number,
        car.year,
        car.color,
        car.type,
        car.body_type,
        car.free,
        car.busy,
        car.service,
        car.all,
    ]);

    const csv = [headers, ...rows]
        .map((row) => row.map((cell) => `"${String(cell ?? '').replace(/"/g, '""')}"`).join(','))
        .join('\n');

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `car-calendar-${currentYear.value}-${String(currentMonth.value).padStart(2, '0')}.csv`;
    link.click();
    URL.revokeObjectURL(url);
};

const toggleHourly = (event) => {
    emit('toggle-hourly', event.target.checked);
};

const prevMonth = () => {
    let year = currentYear.value;
    let month = currentMonth.value;

    if (month === 1) {
        month = 12;
        year--;
    } else {
        month--;
    }

    emit('filter', { year, month });
};

const nextMonth = () => {
    let year = currentYear.value;
    let month = currentMonth.value;

    if (month === 12) {
        month = 1;
        year++;
    } else {
        month++;
    }

    emit('filter', { year, month });
};

</script>

<style scoped>
.calendar-container {
    --day-width: 38px;
    --car-column-width: 260px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    padding: 20px;
    background-color: #fdfdfd;
    color: #333;
}

.top-panel {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 25px;
    flex-wrap: wrap;
    gap: 15px;
}

.toggle-label {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #475467;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
}

.calendar-tools {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 14px;
    flex-wrap: wrap;
}

.search-input,
.status-select {
    height: 34px;
    border: 1px solid #d0d5dd;
    border-radius: 6px;
    background: #fff;
    color: #344054;
    font-size: 13px;
    padding: 0 10px;
}

.search-input {
    min-width: 240px;
}

.status-select {
    min-width: 150px;
    cursor: pointer;
}

.export-btn {
    height: 34px;
    border: 1px solid #98a2b3;
    border-radius: 6px;
    background: #344054;
    color: #fff;
    font-size: 12px;
    font-weight: 800;
    padding: 0 12px;
    cursor: pointer;
}

.export-btn:hover {
    background: #1d2939;
}

.result-count {
    color: #667085;
    font-size: 12px;
    font-weight: 700;
}

.legend-strip {
    display: flex;
    gap: 18px;
    align-items: center;
    margin: -10px 0 14px;
    color: #667085;
    font-size: 12px;
    font-weight: 700;
}

.hourly-panel {
    border: 1px solid #d7e3ff;
    border-radius: 8px;
    background: #f7faff;
    margin: 0 0 14px;
    overflow: hidden;
}

.hourly-panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 10px 12px;
    border-bottom: 1px solid #d7e3ff;
    color: #344054;
    font-size: 13px;
}

.hourly-panel-head span {
    color: #667085;
    font-size: 12px;
    font-weight: 700;
}

.hourly-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 8px;
    padding: 10px;
    max-height: 180px;
    overflow: auto;
}

.hourly-list-item {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 4px 8px;
    align-items: center;
    border: 1px solid #e1e9ff;
    border-radius: 6px;
    background: #fff;
    padding: 8px 10px;
    font-size: 12px;
}

.hourly-car {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: #1d2939;
    font-weight: 800;
}

.hourly-plate,
.hourly-day {
    color: #667085;
    font-weight: 700;
}

.hourly-time {
    color: #2f62c7;
    font-weight: 900;
}

.hourly-empty {
    padding: 12px;
    color: #667085;
    font-size: 13px;
}

.legend-dot {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 999px;
    margin-right: 6px;
}

.rented-dot {
    background: #d66a5f;
}

.service-dot {
    background: #f3c969;
}

.hourly-dot {
    background: #5b8def;
}

.date-navigator {
    display: flex;
    align-items: center;
    gap: 15px;
    font-size: 16px;
    font-weight: 500;
}

.nav-arrow {
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    color: #666;
    padding: 0 10px;
}

.no-data {
    color: #888;
    text-align: center;
    padding: 40px;
    font-size: 1.05rem;
}

.table-responsive {
    width: 100%;
    overflow-x: auto;
    border: 1px solid #e1e4eb;
    border-radius: 6px;
    background-color: white;
}

.availability-table {
    width: max-content;
    min-width: 100%;
    border-collapse: collapse;
    text-align: left;
}

.availability-table th {
    background-color: #ffffff;
    border-bottom: 2px solid #e1e4eb;
    padding: 8px 4px;
    font-weight: normal;
}

.car-info-th {
    width: var(--car-column-width);
    min-width: var(--car-column-width);
    max-width: var(--car-column-width);
    padding-left: 15px !important;
    border-right: 1px solid #e1e4eb;
}

.day-th {
    width: var(--day-width);
    min-width: var(--day-width);
    max-width: var(--day-width);
    text-align: center;
    border-right: 1px solid #f0f2f5;
}

.day-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    font-size: 12px;
}

.day-name {
    color: #8a94a6;
    margin-bottom: 2px;
}

.day-number {
    color: #333;
    font-weight: 500;
}

.availability-table td {
    border-bottom: 1px solid #e1e4eb;
    height: 48px;
    padding: 4px;
    vertical-align: middle;
}

.car-info-td {
    width: var(--car-column-width);
    min-width: var(--car-column-width);
    max-width: var(--car-column-width);
    padding-left: 15px !important;
    border-right: 1px solid #e1e4eb;
    background-color: #fafbfe;
    position: sticky;
    left: 0;
    z-index: 2;
}

.car-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 500;
    min-width: 0;
    flex-wrap: nowrap;
}

.car-title-button {
    min-width: 0;
    flex: 1;
    border: 0;
    background: transparent;
    color: #1d2939;
    font: inherit;
    font-weight: 700;
    text-align: left;
    cursor: pointer;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.car-title-button:hover {
    color: #2f62c7;
}

.details-btn {
    flex: 0 0 auto;
    border: 1px solid #d0d5dd;
    border-radius: 5px;
    background: #fff;
    color: #344054;
    font-size: 11px;
    font-weight: 800;
    padding: 4px 7px;
    cursor: pointer;
}

.details-btn:hover {
    background: #f2f4f7;
}

.modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 50;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(15, 23, 42, .34);
    padding: 20px;
}

.car-modal {
    width: min(520px, 100%);
    border-radius: 10px;
    background: #fff;
    box-shadow: 0 18px 50px rgba(15, 23, 42, .22);
    overflow: hidden;
}

.modal-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    border-bottom: 1px solid #eaecf0;
    padding: 16px 18px;
}

.modal-head h3 {
    margin: 0 0 4px;
    color: #101828;
    font-size: 18px;
}

.modal-head p {
    margin: 0;
    color: #667085;
    font-size: 13px;
    font-weight: 700;
}

.modal-close {
    border: 1px solid #d0d5dd;
    border-radius: 6px;
    background: #fff;
    color: #344054;
    font-size: 12px;
    font-weight: 800;
    padding: 7px 10px;
    cursor: pointer;
}

.car-detail-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
    padding: 16px 18px 18px;
}

.car-detail-grid div {
    border: 1px solid #eaecf0;
    border-radius: 7px;
    background: #fcfcfd;
    padding: 9px 10px;
}

.car-detail-grid dt {
    color: #667085;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
}

.car-detail-grid dd {
    margin: 3px 0 0;
    color: #101828;
    font-size: 14px;
    font-weight: 700;
}

.day-cell {
    border-right: 1px solid #f0f2f5;
    position: relative;
    background-color: #fff;
    padding: 0;
    width: var(--day-width);
    min-width: var(--day-width);
    max-width: var(--day-width);
}

.day-cell:nth-child(7n),
.day-cell:nth-child(7n+1) {
    background-color: #fafbfc;
}

.day-cell.day-rented {
    background-color: #fff;
}

.rental-block,
.service-block {
    position: absolute;
    top: 8px;
    left: 2px;
    z-index: 3;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 3px;
    background-color: #d92d20;
    color: #fff;
    font-size: 12px;
    font-weight: 700;
    line-height: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    padding: 0 7px;
    pointer-events: none;
    box-shadow: 0 1px 2px rgba(16, 24, 40, .12);
}

.rental-block {
    background-color: #d66a5f;
    color: #fff;
}

.service-block {
    background-color: #f3c969;
    color: #513a00;
}

.hourly-indicator {
    position: absolute;
    right: 4px;
    bottom: 4px;
    z-index: 5;
    width: 8px;
    height: 8px;
    border-radius: 999px;
    background: #5b8def;
    border: 2px solid #fff;
    box-shadow: 0 1px 2px rgba(16, 24, 40, .14);
}
</style>
