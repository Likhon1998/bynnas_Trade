<script>
function geoCascade() {
    const urls = {
        divisions: @json(route('field.geo.divisions')),
        districts: @json(route('field.geo.districts')),
        thanas: @json(route('field.geo.thanas')),
        offices: @json(route('field.geo.post-offices')),
        locations: @json(route('field.geo.locations')),
    };
    return {
        locationPick: '',
        locationCustom: '',
        geoOpen: '',
        geoQuery: '',
        geoHi: 0,
        geo: { divisions: [], districts: [], thanas: [], offices: [], locations: [], busy: false, busyDistrict: false, busyThana: false, busyOffice: false, error: '' },
        geoValue(key) {
            if (key === 'location') return this.locationPick || '';
            if (key === 'post_office') return this.f.post_office || '';
            return this.f[key] || '';
        },
        geoPlaceholder(key) {
            if (key === 'division') return this.geo.busy ? 'Loading divisions…' : 'Select division';
            if (key === 'district') return !this.f.division ? 'Pick division first' : (this.geo.busyDistrict ? 'Loading districts…' : 'Select district');
            if (key === 'thana') return !this.f.district ? 'Pick district first' : (this.geo.busyThana ? 'Loading thanas…' : 'Select thana');
            if (key === 'post_office') return !this.f.thana ? 'Pick thana first' : (this.geo.busyOffice ? 'Loading post offices…' : 'Select post office');
            return !this.f.post_office ? 'Pick post office first' : 'Select location';
        },
        geoItems(key) {
            if (key === 'division') return (this.geo.divisions || []).map((name) => ({ value: name, label: name }));
            if (key === 'district') return (this.geo.districts || []).map((name) => ({ value: name, label: name }));
            if (key === 'thana') return (this.geo.thanas || []).map((name) => ({ value: name, label: name }));
            if (key === 'post_office') {
                return (this.geo.offices || []).map((office) => ({
                    value: office.name,
                    label: office.postcode ? (office.name + ' · ' + office.postcode) : office.name,
                }));
            }
            return (this.geo.locations || []).map((name) => ({ value: name, label: name }));
        },
        geoLabel(key) {
            const value = this.geoValue(key);
            if (!value) return this.geoPlaceholder(key);
            if (key === 'location' && value === '__new') return '+ Add a new location';
            const hit = this.geoItems(key).find((item) => item.value === value);
            return hit ? hit.label : value;
        },
        fold(value) {
            return String(value || '').toLowerCase()
                .replace(/dakkhin|daskhin/g, 'dakshin')
                .replace(/[^a-z0-9]+/g, '');
        },
        filteredGeo(key) {
            const q = this.fold(this.geoQuery);
            const items = this.geoItems(key);
            if (!q) return items;
            return items.filter((item) => this.fold(item.label).includes(q) || this.fold(item.value).includes(q));
        },
        toggleGeo(key) {
            if (this.geoOpen === key) { this.closeGeo(); return; }
            this.geoOpen = key;
            this.geoQuery = '';
            this.geoHi = 0;
            this.$nextTick(() => {
                const el = this.$refs['geoSearch' + key];
                if (el) { el.focus(); el.select?.(); }
                if (window.lucide) lucide.createIcons();
            });
        },
        closeGeo() {
            this.geoOpen = '';
            this.geoQuery = '';
            this.geoHi = 0;
        },
        pickGeo(key, value) {
            if (key === 'division') { this.f.division = value; this.onDivision(); }
            else if (key === 'district') { this.f.district = value; this.onDistrict(); }
            else if (key === 'thana') { this.f.thana = value; this.onThana(); }
            else if (key === 'post_office') { this.f.post_office = value; this.onOffice(); }
            else if (key === 'location') { this.locationPick = value; this.onLocationPick(); }
            this.closeGeo();
        },
        onGeoKey(event, key) {
            const items = this.filteredGeo(key);
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                this.geoHi = items.length ? (this.geoHi + 1) % items.length : 0;
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                this.geoHi = items.length ? (this.geoHi - 1 + items.length) % items.length : 0;
            } else if (event.key === 'Enter') {
                event.preventDefault();
                if (items[this.geoHi]) this.pickGeo(key, items[this.geoHi].value);
            } else if (event.key === 'Escape') {
                event.preventDefault();
                this.closeGeo();
            } else {
                this.geoHi = 0;
            }
        },
        async geoGet(url) {
            const res = await fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            if (!res.ok) throw new Error('geo ' + res.status);
            const json = await res.json();
            return json.data || [];
        },
        async bootGeo() {
            await this.loadDivisions();
            await this.applyGeo(this.f || {});
        },
        async loadDivisions() {
            this.geo.busy = true;
            this.geo.error = '';
            try {
                this.geo.divisions = await this.geoGet(urls.divisions);
                if (!this.geo.divisions.length) this.geo.error = 'Could not load divisions. Check internet and retry.';
            } catch (e) {
                this.geo.divisions = [];
                this.geo.error = 'Could not load divisions. Check internet and retry.';
            } finally {
                this.geo.busy = false;
            }
        },
        async loadDistricts(reset = true) {
            const keep = reset ? '' : this.f.district;
            if (reset) {
                this.f.district = '';
                this.geo.districts = [];
                this.f.thana = '';
                this.geo.thanas = [];
                this.f.post_office = '';
                this.geo.offices = [];
                this.resetLocation();
            }
            if (!this.f.division) {
                this.geo.districts = [];
                this.syncCity();
                return;
            }
            this.geo.busyDistrict = true;
            try {
                this.geo.districts = await this.geoGet(urls.districts + '?division=' + encodeURIComponent(this.f.division));
            } catch (e) {
                this.geo.districts = [];
            } finally {
                this.geo.busyDistrict = false;
            }
            if (keep) this.f.district = keep;
            this.syncCity();
        },
        async loadThanas(reset = true) {
            const keep = reset ? '' : this.f.thana;
            if (reset) {
                this.f.thana = '';
                this.geo.thanas = [];
                this.f.post_office = '';
                this.geo.offices = [];
                this.resetLocation();
            }
            if (!this.f.district) {
                this.geo.thanas = [];
                this.syncCity();
                return;
            }
            this.geo.busyThana = true;
            try {
                this.geo.thanas = await this.geoGet(urls.thanas + '?district=' + encodeURIComponent(this.f.district));
            } catch (e) {
                this.geo.thanas = [];
            } finally {
                this.geo.busyThana = false;
            }
            if (keep) this.f.thana = keep;
            this.syncCity();
        },
        async loadOffices(reset = true) {
            const keep = reset ? '' : this.f.post_office;
            if (reset) {
                this.f.post_office = '';
                this.geo.offices = [];
                this.resetLocation();
            }
            if (!this.f.district || !this.f.thana) {
                this.geo.offices = [];
                this.syncCity();
                return;
            }
            this.geo.busyOffice = true;
            try {
                this.geo.offices = await this.geoGet(urls.offices + '?district=' + encodeURIComponent(this.f.district) + '&thana=' + encodeURIComponent(this.f.thana));
            } catch (e) {
                this.geo.offices = [];
            } finally {
                this.geo.busyOffice = false;
            }
            if (keep) this.f.post_office = keep;
            this.syncCity();
        },
        async loadLocations(reset = true) {
            const keepPick = reset ? '' : this.locationPick;
            const keepLoc = reset ? '' : this.f.location;
            if (reset) this.resetLocation();
            if (!this.f.thana) {
                this.geo.locations = [];
                return;
            }
            try {
                this.geo.locations = await this.geoGet(urls.locations + '?thana=' + encodeURIComponent(this.f.thana) + '&post_office=' + encodeURIComponent(this.f.post_office || ''));
            } catch (e) {
                this.geo.locations = [];
            }
            if (keepLoc) this.f.location = keepLoc;
            if (keepPick) this.locationPick = keepPick;
        },
        async applyGeo(values) {
            const v = values || {};
            this.f.division = v.division || '';
            this.f.district = v.district || '';
            this.f.thana = v.thana || '';
            this.f.post_office = v.post_office || '';
            this.f.location = v.location || '';
            if (!this.f.division && v.city) {
                const hit = this.geo.divisions.find((d) => String(d).toLowerCase() === String(v.city).toLowerCase());
                if (hit) this.f.division = hit;
            }
            if (this.f.division) await this.loadDistricts(false);
            if (this.f.district) await this.loadThanas(false);
            if (this.f.thana) await this.loadOffices(false);
            if (this.f.post_office || this.f.thana) await this.loadLocations(false);
            if (this.f.location && this.geo.locations.includes(this.f.location)) {
                this.locationPick = this.f.location;
                this.locationCustom = '';
            } else if (this.f.location) {
                this.locationPick = '__new';
                this.locationCustom = this.f.location;
            } else if (!this.f.division && v.city) {
                this.locationPick = '__new';
                this.locationCustom = v.city;
                this.f.location = v.city;
            } else {
                this.locationPick = '';
                this.locationCustom = '';
            }
            this.syncCity();
        },
        resetLocation() {
            this.locationPick = '';
            this.locationCustom = '';
            this.f.location = '';
        },
        onDivision() { this.loadDistricts(true); },
        onDistrict() { this.loadThanas(true); },
        onThana() { this.loadOffices(true); },
        onOffice() { this.loadLocations(true); },
        onLocationPick() {
            if (this.locationPick === '__new') {
                this.locationCustom = this.locationCustom || '';
                this.syncLocation();
                this.$nextTick(() => this.$refs.locationCustom?.focus());
                return;
            }
            this.locationCustom = '';
            this.f.location = this.locationPick;
            this.f.address = this.f.location;
        },
        syncLocation() {
            this.f.location = this.locationPick === '__new' ? String(this.locationCustom || '').trim() : (this.locationPick || '');
            this.f.address = this.f.location;
        },
        syncCity() {
            this.f.city = this.f.thana || this.f.district || this.f.division || '';
        },
    };
}
</script>
