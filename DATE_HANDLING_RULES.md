# DATE HANDLING RULES - Sistem Absensi

**Problem:** Eloquent's `'field' => 'date'` casting in models causes timezone conversion bugs. Tanggal berubah jadi hari sebelumnya saat di-display di form.

---

## ✅ RULE 1: NEVER cast date fields in Models

### ❌ JANGAN LAKUKAN:
```php
// app/Models/MyModel.php
protected $casts = [
    'my_date' => 'date',  // ❌ JANGAN!
    'my_datetime' => 'datetime',  // ❌ JANGAN!
];
```

### ✅ LAKUKAN:
```php
// app/Models/MyModel.php
protected $casts = [
    // Kosongkan untuk date fields - biarkan tetap string Y-m-d dari database
    // Hanya cast time/datetime jika perlu dengan format spesifik:
    'my_time' => 'datetime:H:i:s',  // ✅ OK untuk time-only fields
];
```

---

## ✅ RULE 2: Return dates sebagai string dari API

### ✅ Di Controller:
```php
// app/Http/Controllers/Admin/MyController.php

public function detail($id) {
    $model = MyModel::findOrFail($id);
    
    // Jangan tambah formatting - return langsung dari toArray()
    $data = $model->toArray();
    
    return response()->json([
        'success' => true,
        'data' => $data  // Date fields otomatis Y-m-d string
    ]);
}
```

---

## ✅ RULE 3: Parse dates hanya di presentation layer

### A. Blade View (Server-side)
```blade
{{-- Untuk DISPLAY (tidak di form input) --}}
{{ \Carbon\Carbon::parse($model->my_date)->format('d/m/Y') }}

{{-- Untuk FORM INPUT --}}
<input type="date" value="{{ $model->my_date }}" name="my_date">
```

### B. JavaScript (Client-side)
```javascript
// ❌ JANGAN gunakan Date constructor:
const date = new Date(data.my_date);  // ❌ Timezone shift!

// ✅ LAKUKAN: String parsing dengan regex atau split:
const dateStr = String(data.my_date).split('T')[0];  // Extract YYYY-MM-DD
const match = dateStr.match(/(\d{4})-(\d{2})-(\d{2})/);
if (match) {
    $('#my_date_input').val(`${match[1]}-${match[2]}-${match[3]}`);
}

// ✅ LAKUKAN: Untuk display (bukan input):
let dateStr = String(data.my_date).split('T')[0];
const [year, month, day] = dateStr.split('-');
const monthNames = ['Januari', 'Februari', 'Maret', ...];
const dateDisplay = `${parseInt(day)} ${monthNames[parseInt(month)-1]} ${year}`;
```

---

## ✅ RULE 4: Checklist untuk fitur baru dengan date field

Sebelum push/commit, pastikan sudah check:

- [ ] **Model**: Tidak ada `'field' => 'date'` atau `'field' => 'datetime'` di `$casts` (kecuali `datetime:H:i:s`)
- [ ] **Migration**: Kolom menggunakan `$table->date('my_date')` atau `$table->dateTime('my_datetime')`
- [ ] **Controller API**: Response return data langsung dengan `toArray()`, tidak di-format manual
- [ ] **Blade View**: Gunakan `\Carbon\Carbon::parse($field)->format(...)` untuk DISPLAY
- [ ] **JavaScript Form Input**: Parse dengan regex/split, bukan `new Date()`
- [ ] **Testing**: Buat record tanggal 15, verify di form input tetap 15 (tidak jadi 14)

---

## 📋 Template untuk Model Baru

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MyModel extends Model
{
    protected $table = 'my_models';
    
    protected $fillable = [
        'name',
        'my_date',      // Date field - TIDAK di-cast
        'description',
    ];
    
    // ❌ JANGAN CAST DATE FIELDS:
    protected $casts = [
        // Kosong atau hanya time-specific casts:
        // 'my_time' => 'datetime:H:i:s',
    ];
}
```

---

## 📋 Template untuk Controller API

```php
public function list() {
    $data = MyModel::all();
    return response()->json([
        'success' => true,
        'data' => $data->toArray()  // Dates auto return sebagai string
    ]);
}

public function detail($id) {
    $model = MyModel::findOrFail($id);
    return response()->json([
        'success' => true,
        'data' => $model->toArray()
    ]);
}
```

---

## 📋 Template untuk Blade View (Form Input)

```blade
<!-- Form input untuk date field -->
<input type="date" 
       id="my_date" 
       name="my_date" 
       value="{{ $model->my_date ?? '' }}" 
       class="form-control">

<!-- Display formatted date (tidak di input) -->
<p>Tanggal: {{ \Carbon\Carbon::parse($model->my_date)->format('d/m/Y') }}</p>
```

---

## 📋 Template untuk JavaScript (Modal/AJAX)

```javascript
$.ajax({
    url: `/api/admin/my-model/${id}`,
    method: 'GET',
    success: function(response) {
        const data = response.data;
        
        // Format date untuk display
        let dateStr = String(data.my_date).split('T')[0];
        let dateDisplay = '-';
        if (dateStr) {
            const [year, month, day] = dateStr.split('-');
            const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
                              'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            if (year && month && day) {
                dateDisplay = `${parseInt(day)} ${monthNames[parseInt(month)-1]} ${year}`;
            }
        }
        
        // Populate form input dengan regex parsing
        const match = dateStr.match(/(\d{4})-(\d{2})-(\d{2})/);
        if (match) {
            $('#my_date_input').val(`${match[1]}-${match[2]}-${match[3]}`);
        }
        
        // Display formatted date
        $('#date_display').html(dateDisplay);
    }
});
```

---

## ⚠️ Exceptions

**Boleh di-cast jika:**
- Time-only field: `'start_time' => 'datetime:H:i:s'` (returns string "HH:mm:ss")
- DateTime dengan timezone handling spesifik di controller (jarang dipakai)

**Contoh OK:**
```php
protected $casts = [
    'start_time' => 'datetime:H:i:s',   // ✅ OK - returns "08:30:00"
    'end_time' => 'datetime:H:i:s',     // ✅ OK - returns "17:00:00"
    'created_at' => 'datetime',         // ✅ OK - Eloquent default
];
```

---

## 🔗 Reference
- Lihat: [copilot-instructions.md](copilot-instructions.md#date-handling--timezone-issues)
- Issue: Eloquent date casting → Carbon serialization → JSON timezone → JS timezone shift
- Solution: Keep as string from DB, parse only di presentation layer

---

**Last Updated:** 2026-06-23
**Applied to:** Attendance, Career, Interview, JoinCall modules
