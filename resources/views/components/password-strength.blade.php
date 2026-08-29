@props(['model' => 'password'])

<div x-data="{
    password: @entangle($model).live,
    get score() {
        if (!this.password) return 0;
        let p = this.password;
        let s = 0;
        if (p.length >= 8) s++;
        if (/[a-z]/.test(p) && /[A-Z]/.test(p)) s++;
        if (/[0-9]/.test(p)) s++;
        if (/[^A-Za-z0-9]/.test(p)) s++;
        return s;
    },
    get label() {
        if (!this.password) return '';
        switch(this.score) {
            case 1: return 'Weak';
            case 2: return 'Fair';
            case 3: return 'Good';
            case 4: return 'Strong';
            default: return 'Too Weak';
        }
    },
    get colorClass() {
        switch(this.score) {
            case 1: return 'bg-error text-error';
            case 2: return 'bg-warning text-warning';
            case 3: return 'bg-info text-info';
            case 4: return 'bg-success text-success';
            default: return 'bg-base-300 text-base-content/40';
        }
    }
}" x-show="password && password.length > 0" x-transition.opacity.duration.200ms class="space-y-1.5 pt-1">
    <div class="flex items-center justify-between text-[11px] font-bold">
        <span class="text-base-content/60">Password Strength:</span>
        <span :class="colorClass.split(' ')[1]" class="font-extrabold uppercase tracking-wide text-[10px]" x-text="label"></span>
    </div>

    <!-- 4-Segment Strength Bar -->
    <div class="grid grid-cols-4 gap-1.5 h-1.5 w-full bg-base-200 rounded-full overflow-hidden p-0.5">
        <div class="h-full rounded-full transition-all duration-300" :class="score >= 1 ? colorClass.split(' ')[0] : 'bg-base-300/50'"></div>
        <div class="h-full rounded-full transition-all duration-300" :class="score >= 2 ? colorClass.split(' ')[0] : 'bg-base-300/50'"></div>
        <div class="h-full rounded-full transition-all duration-300" :class="score >= 3 ? colorClass.split(' ')[0] : 'bg-base-300/50'"></div>
        <div class="h-full rounded-full transition-all duration-300" :class="score >= 4 ? colorClass.split(' ')[0] : 'bg-base-300/50'"></div>
    </div>

    <!-- Helpful Requirements Hints -->
    <div class="flex flex-wrap gap-x-2.5 gap-y-1 text-[10px] text-base-content/60 pt-0.5">
        <span class="flex items-center gap-1" :class="password && password.length >= 8 ? 'text-success font-bold' : ''">
            <span x-text="password && password.length >= 8 ? '✓' : '•'"></span> 8+ chars
        </span>
        <span class="flex items-center gap-1" :class="password && /[a-z]/.test(password) && /[A-Z]/.test(password) ? 'text-success font-bold' : ''">
            <span x-text="password && /[a-z]/.test(password) && /[A-Z]/.test(password) ? '✓' : '•'"></span> Uppercase &amp; Lowercase
        </span>
        <span class="flex items-center gap-1" :class="password && /[0-9]/.test(password) ? 'text-success font-bold' : ''">
            <span x-text="password && /[0-9]/.test(password) ? '✓' : '•'"></span> Number
        </span>
        <span class="flex items-center gap-1" :class="password && /[^A-Za-z0-9]/.test(password) ? 'text-success font-bold' : ''">
            <span x-text="password && /[^A-Za-z0-9]/.test(password) ? '✓' : '•'"></span> Symbol
        </span>
    </div>
</div>
