const ASSET = (name) => `${window.SOKRAT_QUOTE_ASSET_BASE || 'assets'}/${name}`;

const productCatalog = [
  { id: 'custom', name: 'بند مخصص', price: 0, unit: 'قطعه', image: '', description: '' },

  // خدمات وخطوط الكول سنتر
  { id: 'basic-line', name: 'سعر الخط الأساسي', price: 5500, unit: 'قطعه', image: '', description: '' },
  { id: 'extra-line', name: 'سعر خط إضافي', price: 3500, unit: 'قطعه', image: '', description: '' },
  { id: 'special-line-3000', name: 'سعر الخط - عرض 5 خطوط', price: 3000, unit: 'قطعه', image: '', description: 'سعر الخط كما ورد في عرض 5 خطوط' },
  { id: 'landline-programming', name: 'برمجة الخطوط الأرضي', price: 2500, unit: 'قطعه', image: '', description: '' },
  { id: 'gateway-programming', name: 'برمجة الـ Gateway', price: 8000, unit: 'خدمة', image: '', description: 'برمجة وإعداد الـ Gateway' },

  // أجهزة وملحقات الكول سنتر
  { id: 'fxo-card-2-port', name: 'FXO Card 2 Port', price: 5000, unit: 'قطعه', image: '', description: 'FXO card - 2 ports' },
  { id: 'logitech-h340', name: 'Logitech H 340 Mono Headset, Direct USB Cable, Volume Control, 70% Noice Cancellation', price: 1350, unit: 'قطعه', image: ASSET('logitech-h340.png'), description: 'Logitech H340 Mono Headset' },
  { id: 'fiberme-fch7201', name: 'FIBERME FCH7201 Mono USB-A Professional Call Center Headset with Noise-Cancelling Microphone', price: 2050, unit: 'قطعه', image: ASSET('fiberme-fch7201.png'), description: 'Professional call center headset with noise-cancelling microphone' },
  { id: '2b-headset', name: '2B Business USB Headphones with Microphone, Black', price: 1100, unit: 'قطعه', image: ASSET('2b-headset.png'), description: '' },
  { id: 'fap2714p', name: 'FAP2714P Essential IP Phone', price: 1850, unit: 'قطعه', image: ASSET('fap2714p.png'), description: '' },
  { id: 'fap2714w', name: 'FAP2714W Essential IP Phone', price: 2200, unit: 'قطعه', image: ASSET('fap2714w.png'), description: '' },
  { id: 'fag4104', name: 'FAG4104 FXO Gateway, 4 FXO Ports', price: 10200, unit: 'قطعه', image: ASSET('fag4104.png'), description: '' },
  { id: 'fag4108', name: 'FAG4108 FXO Gateway, 8 FXO Ports', price: 17200, unit: 'قطعه', image: ASSET('fag4108.png'), description: '' },
  { id: 'hp-elitedesk', name: 'HP Elite Desk 800 G1 Desktop – PC . i3 -4th 8g ram 256 ssd', price: 5500, unit: 'قطعه', image: ASSET('hp-elitedesk.jpeg'), description: '' },
  { id: 'hp-elitedesk-512-hdd', name: 'HP Elite Desk 800 G1 Desktop – PC . i3 -4th 8g ram 512 HDD', price: 5500, unit: 'قطعه', image: ASSET('hp-elitedesk.jpeg'), description: 'HP EliteDesk 800 G1 - Core i3 4th Gen - 8GB RAM - 512 HDD' },

  // برامج
  { id: 'crm', name: 'CRM System', price: 25000, unit: 'نظام', image: '', description: 'نظام إدارة علاقات العملاء CRM' },
  { id: 'erp', name: 'ERP System', price: 25000, unit: 'نظام', image: '', description: 'نظام تخطيط موارد المؤسسة ERP' }
];

const els = {
  form: document.getElementById('quoteForm'),
  clientName: document.getElementById('clientName'),
  location: document.getElementById('location'),
  preparedBy: document.getElementById('preparedBy'),
  quotationNo: document.getElementById('quotationNo'),
  quoteDate: document.getElementById('quoteDate'),
  systemTitle: document.getElementById('systemTitle'),
  proposalDescription: document.getElementById('proposalDescription'),
  itemsEditor: document.getElementById('itemsEditor'),
  itemTemplate: document.getElementById('itemRowTemplate'),
  formGrandTotal: document.getElementById('formGrandTotal'),
  technicalScope: document.getElementById('technicalScope'),
  terms: document.getElementById('terms'),
  features: document.getElementById('features'),
  includeProducts: document.getElementById('includeProducts'),
  includeTechnical: document.getElementById('includeTechnical'),
  includeTerms: document.getElementById('includeTerms'),
  includeFeatures: document.getElementById('includeFeatures'),
  quoteFontFamily: document.getElementById('quoteFontFamily'),
  quoteFontSize: document.getElementById('quoteFontSize'),
  quoteFontSizeValue: document.getElementById('quoteFontSizeValue'),
  enableTextColor: document.getElementById('enableTextColor'),
  quoteTextColor: document.getElementById('quoteTextColor'),
  enableAccentColor: document.getElementById('enableAccentColor'),
  quoteAccentColor: document.getElementById('quoteAccentColor'),
  quotePreview: document.getElementById('quotePreview'),
  pageCount: document.getElementById('pageCount')
};

let items = [];
let nextItemId = 1;

const quoteFonts = {
  arial: 'Arial, Tahoma, sans-serif',
  tahoma: 'Tahoma, Arial, sans-serif',
  traditional: 'Traditional Arabic, Tahoma, Arial, sans-serif',
  simplified: 'Simplified Arabic, Tahoma, Arial, sans-serif',
  times: 'Times New Roman, Times, serif',
  verdana: 'Verdana, Tahoma, Arial, sans-serif',
  georgia: 'Georgia, Times New Roman, serif'
};

function getTypographySettings() {
  const sizePercent = Math.min(130, Math.max(85, Number(els.quoteFontSize?.value || 100)));
  // 5% step = 0.18mm. This keeps the A4 layout stable while still giving visible control.
  const bumpMm = ((sizePercent - 100) / 5) * 0.18;
  return {
    fontFamily: quoteFonts[els.quoteFontFamily?.value] || quoteFonts.arial,
    sizePercent,
    bumpMm,
    useTextColor: Boolean(els.enableTextColor?.checked),
    textColor: els.quoteTextColor?.value || '#111111',
    useAccentColor: Boolean(els.enableAccentColor?.checked),
    accentColor: els.quoteAccentColor?.value || '#e10d0d'
  };
}

function typographyPageAttrs() {
  const t = getTypographySettings();
  const classes = [t.useTextColor ? 'custom-text-color' : '', t.useAccentColor ? 'custom-accent-color' : ''].filter(Boolean).join(' ');
  const style = `--content-font:${t.fontFamily};--content-font-bump:${t.bumpMm.toFixed(2)}mm;--content-color:${t.textColor};--accent-color:${t.accentColor};`;
  return { classes, style };
}

function syncTypographyControls() {
  if (els.quoteFontSizeValue) els.quoteFontSizeValue.textContent = `${els.quoteFontSize.value}%`;
  if (els.quoteTextColor) els.quoteTextColor.disabled = !els.enableTextColor.checked;
  if (els.quoteAccentColor) els.quoteAccentColor.disabled = !els.enableAccentColor.checked;
}

function resetTypography() {
  els.quoteFontFamily.value = 'arial';
  els.quoteFontSize.value = '100';
  els.enableTextColor.checked = false;
  els.quoteTextColor.value = '#111111';
  els.enableAccentColor.checked = false;
  els.quoteAccentColor.value = '#e10d0d';
  syncTypographyControls();
  updateAll();
}

function todayISO() {
  const d = new Date();
  const local = new Date(d.getTime() - d.getTimezoneOffset() * 60000);
  return local.toISOString().slice(0, 10);
}
els.quoteDate.value = todayISO();

function escapeHTML(value = '') {
  return String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

function money(value) {
  const n = Number(value || 0);
  if (!Number.isFinite(n)) return '0';
  return Number.isInteger(n) ? String(n) : n.toFixed(2).replace(/\.00$/, '');
}

function lineBreaks(value = '') {
  return escapeHTML(value).replace(/\n/g, '<br>');
}

function catalogOptions(selected = 'custom') {
  return productCatalog.map(p => {
    const priceLabel = p.id === 'custom' ? '' : ` — ${money(p.price)} جنيه`;
    return `<option value="${p.id}" ${p.id === selected ? 'selected' : ''}>${escapeHTML(p.name)}${priceLabel}</option>`;
  }).join('');
}

function getCatalogProduct(id) {
  return productCatalog.find(p => p.id === id) || productCatalog[0];
}

function addItem(initial = {}) {
  const rowId = nextItemId++;
  const productId = initial.productId || 'custom';
  const product = getCatalogProduct(productId);
  const item = {
    id: rowId,
    productId,
    name: initial.name ?? (productId === 'custom' ? '' : product.name),
    description: initial.description ?? product.description,
    qty: Number(initial.qty ?? 1),
    price: Number(initial.price ?? product.price ?? 0),
    unit: initial.unit ?? product.unit ?? 'قطعه',
    image: initial.image ?? product.image ?? '',
    showProduct: Boolean(initial.showProduct ?? Boolean(product.image))
  };
  items.push(item);
  renderItemEditor();
  updateAll();
}

function removeItem(id) {
  items = items.filter(i => i.id !== id);
  if (!items.length) addItem();
  else {
    renderItemEditor();
    updateAll();
  }
}

function renderItemEditor() {
  els.itemsEditor.innerHTML = '';
  items.forEach(item => {
    const node = els.itemTemplate.content.firstElementChild.cloneNode(true);
    node.dataset.id = item.id;
    const select = node.querySelector('.item-product');
    const custom = node.querySelector('.item-custom-name');
    const description = node.querySelector('.item-description');
    const qty = node.querySelector('.item-qty');
    const price = node.querySelector('.item-price');
    const unit = node.querySelector('.item-unit');
    const total = node.querySelector('.item-total');
    const show = node.querySelector('.item-show-product');
    const imageInput = node.querySelector('.item-image');

    select.innerHTML = catalogOptions(item.productId);
    custom.hidden = item.productId !== 'custom';
    custom.value = item.name;
    description.value = item.description || '';
    qty.value = item.qty;
    price.value = item.price;
    unit.value = item.unit;
    total.textContent = money(item.qty * item.price);
    show.checked = item.showProduct;

    select.addEventListener('change', () => {
      const p = getCatalogProduct(select.value);
      item.productId = p.id;
      if (p.id === 'custom') {
        custom.hidden = false;
        item.name = custom.value || '';
        item.image = '';
        item.showProduct = false;
        show.checked = false;
      } else {
        custom.hidden = true;
        item.name = p.name;
        item.description = p.description;
        item.price = p.price;
        item.unit = p.unit;
        item.image = p.image;
        item.showProduct = Boolean(p.image);
        description.value = item.description;
        price.value = item.price;
        unit.value = item.unit;
        show.checked = item.showProduct;
      }
      updateAll();
    });
    custom.addEventListener('input', () => { item.name = custom.value; updateAll(); });
    description.addEventListener('input', () => { item.description = description.value; updateAll(); });
    qty.addEventListener('input', () => { item.qty = Number(qty.value || 0); total.textContent = money(item.qty * item.price); updateAll(); });
    price.addEventListener('input', () => { item.price = Number(price.value || 0); total.textContent = money(item.qty * item.price); updateAll(); });
    unit.addEventListener('input', () => { item.unit = unit.value; updateAll(); });
    show.addEventListener('change', () => { item.showProduct = show.checked; updateAll(); });
    imageInput.addEventListener('change', (event) => {
      const file = event.target.files?.[0];
      if (!file) return;
      const reader = new FileReader();
      reader.onload = () => {
        item.image = reader.result;
        item.showProduct = true;
        show.checked = true;
        updateAll();
      };
      reader.readAsDataURL(file);
    });
    node.querySelector('.remove-item').addEventListener('click', () => removeItem(item.id));
    els.itemsEditor.appendChild(node);
  });
}

function grandTotal() {
  return items.reduce((sum, item) => sum + (Number(item.qty) || 0) * (Number(item.price) || 0), 0);
}

function headerHTML(first = false) {
  return `
    <header class="q-header">
      <div class="q-header-red"></div>
      <div class="q-header-black"></div>
      <div class="q-brand"><strong>Socrates pro tech</strong><span>For trading &amp; system</span></div>
      <div class="q-title">Quotation</div>
      ${first ? `<div class="q-meta">
        <span>Quotation No</span><strong>${escapeHTML(els.quotationNo.value || '—')}</strong>
        <span>Date</span><strong>${escapeHTML(formatDate(els.quoteDate.value) || '—')}</strong>
      </div>` : ''}
    </header>`;
}

function footerHTML() {
  return `
    <footer class="q-footer">
      <div class="q-footer-black"></div>
      <div class="q-footer-red"></div>
      <div class="q-footer-contact">
        <div class="q-phone-symbol">☎</div>
        <div class="q-phone-lines"><span>01001327609</span><span>0233033 829</span></div>
        <img class="q-map" src="${ASSET('map-icon.png')}" alt="" />
        <div class="q-address">64 b El Rashied st,<br>El Mohandssen</div>
      </div>
      <div class="q-website">www.sokrattech.com</div>
      <img class="q-st-logo" src="${ASSET('st-logo.png')}" alt="ST" />
    </footer>`;
}

function page(content, className = '', first = false) {
  const typography = typographyPageAttrs();
  return `<section class="quote-page ${className} ${typography.classes}" style="${typography.style}">${headerHTML(first)}${content}${footerHTML()}</section>`;
}

function formatDate(dateStr) {
  if (!dateStr) return '';
  const [y,m,d] = dateStr.split('-');
  return `${Number(y)}-${Number(m)}-${Number(d)}`;
}

function coverPageHTML() {
  const client = escapeHTML(els.clientName.value || 'اسم العميل / الشركة');
  const loc = escapeHTML(els.location.value || 'الموقع');
  const prepared = escapeHTML(els.preparedBy.value || '—');
  return page(`
    <div class="q-body cover-body">
      <div class="client-card-wrap">
        <div class="client-card-title">Quotation to</div>
        <div class="client-card">
          <div>${client}</div>
          <div>${loc}</div>
          <div>مقدمه : ${prepared}</div>
        </div>
      </div>
      <img class="cover-circuit" src="${ASSET('circuit.jpeg')}" alt="" />
      <div class="cover-copy">
        <p class="greeting">السلام عليكم و رحمة الله وبركاته ....</p>
        <p class="intro"><strong>سقراط تك</strong> شركة رائدة فى كل ما يخص تكنولوجيا المعلومات بتخصص و دقة . لدينا فريق عمل قوى فى كل مجالات الاعمال التى نقوم بها يشرفنا ان نضعها بين ايديكم</p>
        <ul class="cover-services">
          <li>أعمال الانظمة الامنيه ( كاميرات المراقبة – اجهزة الانذار ضد السرقه – البوابات الامنية .. )</li>
          <li>الاعمال الادارية ( اجهزة الحضور و الانصراف – ماكينات عد النقدية – الطابعات بكافة انواعها – طابعات طباعة الكروت – ماكينات التصوير .. )</li>
          <li>اعمال الاتصالات ( سنترالات – call center – دش مركزي .... )</li>
          <li>انظمة الصوت و الاستدعاء و الطوابير</li>
          <li>انظمة الشبكات والخوادم</li>
          <li>انظمة العرض – شاشات العرض العملاقة – بروجيكتور ...</li>
          <li>اللاب توب و الكمبيوتر و اكسسوارات الكمبيوتر</li>
          <li>قسم خاص باعمال صيانة الالكترونيات</li>
          <li>برامج ادارية و محاسبية و انظمة نقاط البيع و الباركود</li>
        </ul>
        <p class="cover-end">نعتذر للاطالة لدينا دائما المزيد برجاء الاستفسار اى وقت و لنري العروض المقدمه اليكم</p>
      </div>
      <img class="cover-robot" src="${ASSET('robot-arm.jpeg')}" alt="" />
    </div>`, 'cover-page', true);
}

function financialPageHTML() {
  const client = escapeHTML(els.clientName.value || 'اسم العميل / الشركة');
  const title = escapeHTML(els.systemTitle.value || 'Call Center System');
  const description = lineBreaks(els.proposalDescription.value);
  const rows = items.map(item => {
    const total = item.qty * item.price;
    const desc = item.name || '—';
    return `<tr>
      <td class="desc-cell">${escapeHTML(desc)}</td>
      <td>${money(item.qty)}</td>
      <td>${money(item.price)}</td>
      <td>${escapeHTML(item.unit || 'قطعه')}</td>
      <td>${money(total)}</td>
    </tr>`;
  }).join('');
  const total = grandTotal();
  return page(`
    <div class="q-body financial-body">
      <div class="financial-copy">
        <div class="recipient">السادة / ${client}</div>
        <p>كل التحية...</p>
        <p>${description}</p>
      </div>
      <div class="section-title">المقايسة المالية</div>
      <table class="financial-table">
        <colgroup><col style="width:46%"><col style="width:10%"><col style="width:15%"><col style="width:13%"><col style="width:16%"></colgroup>
        <thead>
          <tr class="system-head"><th colspan="5">${title}</th></tr>
          <tr class="columns"><th>الصنف</th><th>العدد</th><th>سعر القطعه</th><th>الوحده</th><th>الاجمالى</th></tr>
        </thead>
        <tbody>${rows}</tbody>
        <tfoot><tr><td colspan="4">الاجمالى</td><td>${money(total)}</td></tr></tfoot>
      </table>
      <div class="financial-summary">تم الاتفاق على عمل عرض خاص لكم بإجمالي <strong>${money(total)}</strong> جنيه مصري فقط لا غير</div>
      <div class="agreement-line">ويتم العمل بهذا العرض حسب الاتفاق</div>
    </div>`, 'financial-page');
}

function productPagesHTML() {
  if (!els.includeProducts.checked) return [];
  const selected = items.filter(item => item.showProduct);
  if (!selected.length) return [];
  const pages = [];
  for (let i = 0; i < selected.length; i += 2) {
    const chunk = selected.slice(i, i + 2);
    const cards = chunk.map(item => productCardHTML(item)).join('');
    const placeholder = chunk.length === 1 ? `<div class="product-card"><div class="product-placeholder">Optional product</div></div>` : '';
    pages.push(page(`
      <div class="q-body product-body">
        <div class="product-page-title ${i === 0 ? 'red' : ''}">${i === 0 ? 'الملحقات' : ''}</div>
        <div class="product-grid">${cards}${placeholder}</div>
      </div>`, 'product-page'));
  }
  return pages;
}

function productCardHTML(item) {
  const image = item.image
    ? `<img src="${item.image}" alt="${escapeHTML(item.name)}" />`
    : `<div class="product-placeholder">No Image</div>`;
  return `<article class="product-card">
    ${image}
    <h3>${escapeHTML(item.name || 'Product')}</h3>
    ${item.description ? `<div class="product-description">${escapeHTML(item.description)}</div>` : ''}
    <div class="product-price">بسعر <strong>${money(item.price)}</strong> جنيه</div>
  </article>`;
}

function linesToDivs(text) {
  return String(text || '').split(/\n+/).filter(Boolean).map(line => `<div class="line">${escapeHTML(line)}</div>`).join('');
}

function technicalPageHTML() {
  const showTechnical = els.includeTechnical.checked;
  const showTerms = els.includeTerms.checked;
  if (!showTechnical && !showTerms) return '';
  return page(`
    <div class="q-body text-page-body">
      ${showTechnical ? `<div class="text-section-title">المقايسة الفنية</div><div class="text-lines">${linesToDivs(els.technicalScope.value)}</div>` : ''}
      ${showTerms ? `<div class="terms-block"><div class="text-section-title">الشروط و الاتفاقات</div><div class="text-lines">${linesToDivs(els.terms.value)}</div></div>` : ''}
    </div>`, 'technical-page');
}

function featuresPageHTML() {
  if (!els.includeFeatures.checked) return '';
  const features = String(els.features.value || '').split(/\n+/).map(s => s.trim()).filter(Boolean);
  return page(`
    <div class="q-body text-page-body">
      <div class="text-section-title">بعض مميزات و خصائص برنامج الكول سنتر</div>
      <div class="features-intro">دلوقتي تقدر تعمل تطوير شامل لمنظومة الاتصالات في مؤسستك مهما كان حجمها. خدمة العملاء اليوم قسم مهم جدا لأي نشاط، وتقدر تربط كل ده في مكان واحد يدعم الرسائل المسجلة والتقارير وتسجيل المكالمات.</div>
      <div class="features-list">${features.map(feature => `<div class="feature-item">${escapeHTML(feature.replace(/^[-–]\s*/, ''))}</div>`).join('')}</div>
      <div class="closing">واليكم كل التحية و التقدير<br>و يشرفنا العمل معكم.</div>
    </div>`, 'features-page');
}

function buildPreview() {
  const pageList = [coverPageHTML(), financialPageHTML(), ...productPagesHTML()];
  const tech = technicalPageHTML();
  const features = featuresPageHTML();
  if (tech) pageList.push(tech);
  if (features) pageList.push(features);
  els.quotePreview.innerHTML = pageList.join('');
  els.pageCount.textContent = `عدد الصفحات: ${pageList.length}`;
}

function updateAll() {
  els.formGrandTotal.textContent = money(grandTotal());
  buildPreview();
}

function loadDemo() {
  els.clientName.value = 'الحشاش للأثاث';
  els.location.value = 'المقطم';
  els.preparedBy.value = 'احمد حمدي';
  els.quotationNo.value = '78695455';
  els.quoteDate.value = '2026-08-09';
  els.systemTitle.value = 'Call Center System';
  items = [];
  addItem({ productId: 'basic-line', qty: 1, price: 5500, showProduct: false });
  addItem({ productId: 'extra-line', qty: 2, price: 3500, showProduct: false });
  addItem({ productId: 'fap2714p', qty: 1, price: 1850, showProduct: true });
  addItem({ productId: 'logitech-h340', qty: 2, price: 1350, showProduct: true });
  updateAll();
}

function resetForm() {
  if (!confirm('هل تريد مسح بيانات عرض السعر والبدء من جديد؟')) return;
  els.form.reset();
  els.preparedBy.value = 'احمد حمدي';
  els.quoteDate.value = todayISO();
  els.systemTitle.value = 'Call Center System';
  els.includeProducts.checked = true;
  els.includeTechnical.checked = true;
  els.includeTerms.checked = true;
  els.includeFeatures.checked = true;
  els.quoteFontFamily.value = 'arial';
  els.quoteFontSize.value = '100';
  els.enableTextColor.checked = false;
  els.quoteTextColor.value = '#111111';
  els.enableAccentColor.checked = false;
  els.quoteAccentColor.value = '#e10d0d';
  syncTypographyControls();
  items = [];
  addItem();
}

function printQuote() {
  buildPreview();
  setTimeout(() => window.print(), 50);
}

// Buttons
document.getElementById('addItemBtn').addEventListener('click', () => addItem());
document.getElementById('previewBtn').addEventListener('click', () => {
  buildPreview();
  document.querySelector('.preview-stage').scrollIntoView({ behavior: 'smooth', block: 'start' });
});
document.getElementById('printBtn').addEventListener('click', printQuote);
document.getElementById('printTopBtn').addEventListener('click', printQuote);
document.getElementById('loadDemoBtn').addEventListener('click', loadDemo);
document.getElementById('resetBtn').addEventListener('click', resetForm);
document.getElementById('resetTypographyBtn').addEventListener('click', resetTypography);

[els.quoteFontFamily, els.quoteFontSize, els.quoteTextColor, els.quoteAccentColor].forEach(control => {
  control.addEventListener('input', () => { syncTypographyControls(); updateAll(); });
  control.addEventListener('change', () => { syncTypographyControls(); updateAll(); });
});
[els.enableTextColor, els.enableAccentColor].forEach(control => {
  control.addEventListener('change', () => { syncTypographyControls(); updateAll(); });
});

document.querySelectorAll('.collapse-trigger').forEach(btn => {
  btn.addEventListener('click', () => btn.closest('.collapsible').classList.toggle('open'));
});

// Live preview for non-item fields.
els.form.addEventListener('input', event => {
  if (!event.target.closest('.item-row')) updateAll();
});
els.form.addEventListener('change', event => {
  if (!event.target.closest('.item-row')) updateAll();
});

// Start with a useful blank line item.
syncTypographyControls();
addItem({ productId: 'basic-line', qty: 1, price: 5500, showProduct: false });
addItem({ productId: 'custom', name: '', qty: 1, price: 0, showProduct: false });
updateAll();
