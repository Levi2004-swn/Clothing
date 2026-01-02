<?php
require_once 'config.php';

$page_title = 'Size Guide - ' . SITE_NAME;
include 'header.php';
?>

<style>
/* Scoped styles for Size Guide page */
.size-hero { background: #ecfeff; border: 1px solid #a5f3fc; padding: 28px; border-radius: 12px; margin: 24px 0; }
.size-hero h1 { margin: 0 0 8px; font-size: 1.8rem; color: #075985; }
.size-hero p { color: #0e7490; margin: 0; }

.size-badges { display: flex; flex-wrap: wrap; gap: 10px; margin: 16px 0 0; }
.size-badges .badge { display: inline-flex; align-items: center; gap: 8px; background: #f1f5f9; color: #0f172a; padding: 8px 12px; border-radius: 999px; font-weight: 600; border: 1px solid #e2e8f0; }
.size-badges .badge i { color: #06b6d4; }

/* Actions */
.size-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 12px; }
.print-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 14px; border-radius: 8px; text-decoration: none; font-weight: 700; background: #f1f5f9; color: #0f172a; border: 1px solid #e2e8f0; }
.print-btn:hover { background: #e2e8f0; }
.print-btn i { color: #0ea5e9; }

.tabs { display: flex; flex-wrap: wrap; gap: 8px; margin: 16px 0; }
.tab { cursor: pointer; border: 1px solid #e5e7eb; background: #f8fafc; color: #111827; padding: 8px 12px; border-radius: 999px; font-weight: 700; }
.tab.active { background: #f53d2d; color: #fff; border-color: #f53d2d; }

.card { border: 1px solid #e5e7eb; border-radius: 10px; background: #fff; padding: 16px; margin-bottom: 16px; }
.card h3 { margin: 0 0 10px; font-size: 1.1rem; color: #111827; }

.table-wrap { overflow-x: auto; }
.table { width: 100%; border-collapse: collapse; }
.table th, .table td { border: 1px solid #e5e7eb; padding: 10px; text-align: center; font-size: 0.95rem; color: #374151; }
.table th { background: #f9fafb; color: #111827; }

.tip { background: #fff7ed; border: 1px solid #fed7aa; color: #9a3412; padding: 12px 14px; border-radius: 8px; margin: 8px 0; }
.note { background: #eef2ff; border: 1px solid #c7d2fe; color: #3730a3; padding: 12px 14px; border-radius: 8px; margin: 8px 0; }
.small { font-size: 0.92rem; }
.hidden { display: none; }

/* Measurement visual guide */
.measure-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px; }
.measure-card { border: 1px dashed #e5e7eb; border-radius: 10px; background: #fff; padding: 12px; text-align: center; }
.measure-card h4 { margin: 8px 0 0; font-size: 0.95rem; color: #0f172a; }
.measure-svg { width: 120px; height: 80px; }
.measure-line { stroke: #f53d2d; stroke-width: 3; }
.measure-ghost { stroke: #94a3b8; stroke-width: 2; fill: none; stroke-dasharray: 4 4; }

/* Print-friendly styles */
@media print {
    .top-bar, .main-header, .main-footer, .size-hero, .tabs, .print-btn { display: none !important; }
    body { background: #fff; }
    .container { margin: 0; padding: 0; }
    .card { page-break-inside: avoid; border-color: #9ca3af; }
    a[href]:after { content: "" !important; }
}
</style>

<div class="container" style="margin-top: 20px; margin-bottom: 60px;">
    <div class="size-hero">
        <h1><i class="fas fa-ruler"></i> Size Guide</h1>
        <p>Find your perfect fit. Measure yourself and compare with the charts below. This page is informational only and does not change your checkout or returns workflow.</p>
        <div class="size-badges">
            <span class="badge"><i class="fas fa-venus-mars"></i> Men / Women / Kids</span>
            <span class="badge"><i class="fas fa-shirt"></i> Tops / Bottoms</span>
            <span class="badge"><i class="fas fa-shoe-prints"></i> Footwear</span>
            <span class="badge"><i class="fas fa-arrows-left-right"></i> Conversions</span>
        </div>
        <div class="size-actions">
            <button type="button" class="print-btn" id="printGuide"><i class="fas fa-print"></i> Print size guide</button>
        </div>
    </div>

    <div class="card">
        <h3>How to Measure</h3>
        <div class="tip small"><strong>Chest:</strong> Measure around the fullest part of your chest, keeping the tape horizontal.</div>
        <div class="tip small"><strong>Waist:</strong> Measure around your natural waistline (above the hips), keeping the tape snug but not tight.</div>
        <div class="tip small"><strong>Hips:</strong> Measure around the fullest part of your hips.</div>
        <div class="tip small"><strong>Inseam (Bottoms):</strong> Measure from the top of your inner thigh to the bottom of your ankle.</div>
        <div class="tip small"><strong>Foot (Shoes):</strong> Stand on paper, mark heel to longest toe; measure length and compare to chart.</div>
    </div>

    <div class="card">
        <h3>Measurement Guide (Visual)</h3>
        <div class="measure-grid">
            <div class="measure-card">
                <svg class="measure-svg" viewBox="0 0 120 80" aria-hidden="true">
                    <path class="measure-ghost" d="M40 70 C40 40, 80 40, 80 70"/>
                    <line class="measure-line" x1="35" y1="40" x2="85" y2="40"/>
                </svg>
                <h4>Chest / Bust</h4>
            </div>
            <div class="measure-card">
                <svg class="measure-svg" viewBox="0 0 120 80" aria-hidden="true">
                    <path class="measure-ghost" d="M50 70 C50 50, 70 50, 70 70"/>
                    <line class="measure-line" x1="45" y1="50" x2="75" y2="50"/>
                </svg>
                <h4>Waist</h4>
            </div>
            <div class="measure-card">
                <svg class="measure-svg" viewBox="0 0 120 80" aria-hidden="true">
                    <path class="measure-ghost" d="M40 70 C40 60, 80 60, 80 70"/>
                    <line class="measure-line" x1="35" y1="60" x2="85" y2="60"/>
                </svg>
                <h4>Hips</h4>
            </div>
            <div class="measure-card">
                <svg class="measure-svg" viewBox="0 0 120 80" aria-hidden="true">
                    <line class="measure-ghost" x1="60" y1="20" x2="60" y2="70"/>
                    <line class="measure-line" x1="60" y1="30" x2="60" y2="70"/>
                </svg>
                <h4>Inseam</h4>
            </div>
            <div class="measure-card">
                <svg class="measure-svg" viewBox="0 0 120 80" aria-hidden="true">
                    <path class="measure-ghost" d="M20 55 Q40 30 85 40 Q90 45 95 50"/>
                    <line class="measure-line" x1="22" y1="60" x2="95" y2="60"/>
                </svg>
                <h4>Foot Length</h4>
            </div>
        </div>
    </div>

    <div class="tabs" id="sizeTabs">
        <button class="tab active" data-target="men">Men</button>
        <button class="tab" data-target="women">Women</button>
        <button class="tab" data-target="kids">Kids</button>
        <button class="tab" data-target="shirts">Shirts & Tops</button>
        <button class="tab" data-target="pants">Pants</button>
        <button class="tab" data-target="shorts">Shorts</button>
        <button class="tab" data-target="shoes">Shoes</button>
    </div>

    <!-- Men -->
    <div class="section" id="men">
        <div class="card">
            <h3>Men's General Apparel</h3>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Size</th><th>Chest (cm)</th><th>Waist (cm)</th><th>Hips (cm)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>XS</td><td>84–89</td><td>70–75</td><td>84–89</td></tr>
                        <tr><td>S</td><td>90–95</td><td>76–81</td><td>90–95</td></tr>
                        <tr><td>M</td><td>96–101</td><td>82–87</td><td>96–101</td></tr>
                        <tr><td>L</td><td>102–109</td><td>88–95</td><td>102–109</td></tr>
                        <tr><td>XL</td><td>110–117</td><td>96–103</td><td>110–117</td></tr>
                        <tr><td>XXL</td><td>118–125</td><td>104–111</td><td>118–125</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Women -->
    <div class="section hidden" id="women">
        <div class="card">
            <h3>Women's General Apparel</h3>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Size</th><th>Bust (cm)</th><th>Waist (cm)</th><th>Hips (cm)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>XS</td><td>78–83</td><td>60–65</td><td>85–90</td></tr>
                        <tr><td>S</td><td>84–89</td><td>66–71</td><td>91–96</td></tr>
                        <tr><td>M</td><td>90–95</td><td>72–77</td><td>97–102</td></tr>
                        <tr><td>L</td><td>96–103</td><td>78–85</td><td>103–110</td></tr>
                        <tr><td>XL</td><td>104–111</td><td>86–93</td><td>111–118</td></tr>
                        <tr><td>XXL</td><td>112–119</td><td>94–101</td><td>119–126</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Kids -->
    <div class="section hidden" id="kids">
        <div class="card">
            <h3>Kids (Approximate by Height)</h3>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr><th>Size</th><th>Age</th><th>Height (cm)</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>XS</td><td>3–4</td><td>98–104</td></tr>
                        <tr><td>S</td><td>5–6</td><td>110–116</td></tr>
                        <tr><td>M</td><td>7–8</td><td>122–128</td></tr>
                        <tr><td>L</td><td>9–10</td><td>134–140</td></tr>
                        <tr><td>XL</td><td>11–12</td><td>146–152</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Shirts & Tops -->
    <div class="section hidden" id="shirts">
        <div class="card">
            <h3>Shirts & Tops (Unisex Guide)</h3>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr><th>Alpha</th><th>Chest (cm)</th><th>Suggested Height</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>XS</td><td>84–89</td><td>Up to 165 cm</td></tr>
                        <tr><td>S</td><td>90–95</td><td>160–175 cm</td></tr>
                        <tr><td>M</td><td>96–101</td><td>170–180 cm</td></tr>
                        <tr><td>L</td><td>102–109</td><td>175–185 cm</td></tr>
                        <tr><td>XL</td><td>110–117</td><td>180–190 cm</td></tr>
                        <tr><td>XXL</td><td>118–125</td><td>185+ cm</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pants -->
    <div class="section hidden" id="pants">
        <div class="card">
            <h3>Pants (Waist/Inseam)</h3>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr><th>Size</th><th>Waist (cm)</th><th>Inseam (cm)</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>28</td><td>71–73</td><td>76–78</td></tr>
                        <tr><td>30</td><td>76–78</td><td>78–81</td></tr>
                        <tr><td>32</td><td>81–83</td><td>81–83</td></tr>
                        <tr><td>34</td><td>86–88</td><td>83–86</td></tr>
                        <tr><td>36</td><td>91–93</td><td>86–89</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="note small">Tip: If you are between sizes, consider the larger size for a more relaxed fit.</div>
        </div>
    </div>

    <!-- Shorts -->
    <div class="section hidden" id="shorts">
        <div class="card">
            <h3>Shorts (Waist)</h3>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr><th>Alpha</th><th>Waist (cm)</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>XS</td><td>70–75</td></tr>
                        <tr><td>S</td><td>76–81</td></tr>
                        <tr><td>M</td><td>82–87</td></tr>
                        <tr><td>L</td><td>88–95</td></tr>
                        <tr><td>XL</td><td>96–103</td></tr>
                        <tr><td>XXL</td><td>104–111</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Shoes -->
    <div class="section hidden" id="shoes">
        <div class="card">
            <h3>Shoe Size Conversion</h3>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr><th>US</th><th>UK</th><th>EU</th><th>Length (cm)</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>6</td><td>5.5</td><td>38.5</td><td>24.5</td></tr>
                        <tr><td>7</td><td>6.5</td><td>40</td><td>25.4</td></tr>
                        <tr><td>8</td><td>7.5</td><td>41</td><td>26.2</td></tr>
                        <tr><td>9</td><td>8.5</td><td>42.5</td><td>27.1</td></tr>
                        <tr><td>10</td><td>9.5</td><td>44</td><td>27.9</td></tr>
                        <tr><td>11</td><td>10.5</td><td>45</td><td>28.8</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="note small">Note: Conversions are approximate and may vary by brand/style.</div>
        </div>
    </div>
</div>

<script>
// Simple tab switcher (scoped)
(function(){
    var tabs = document.querySelectorAll('#sizeTabs .tab');
    var sections = document.querySelectorAll('.section');
    function activate(id){
        tabs.forEach(function(t){ t.classList.toggle('active', t.getAttribute('data-target') === id); });
        sections.forEach(function(s){ s.classList.toggle('hidden', s.id !== id); });
        // Scroll into view on mobile for better UX
        var active = document.getElementById(id);
        if (active) { active.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
    }
    tabs.forEach(function(tab){
        tab.addEventListener('click', function(){ activate(this.getAttribute('data-target')); });
    });
    // Print handler
    var printBtn = document.getElementById('printGuide');
    if (printBtn) {
        printBtn.addEventListener('click', function(){ window.print(); });
    }
})();
</script>

<?php include 'footer.php'; ?>
