@extends('layouts.admin-modern')

@section('title', 'Virements')
@section('page_title', 'Virements')
@section('nav_active', 'gepay')

@section('style')
<style>
:root {
    --c-green:  #009543; --c-green-bg: #f0fdf4; --c-green-bd: #bbf7d0;
    --c-teal:   #0284c7; --c-teal-bg:  #f0f9ff; --c-teal-bd:  #bae6fd;
    --c-amber:  #d97706; --c-amber-bg: #fffbeb; --c-amber-bd: #fde68a;
    --c-red:    #dc2626; --c-red-bg:   #fef2f2; --c-red-bd:   #fecaca;
    --c-bd:  #e5e7eb; --c-bg2: #f8fafc; --c-txt: #111827; --c-mt: #6b7280;
    --mtn: #ffc200; --air: #e4002b;
}

/* KPIs */
.v-kpis { display:flex; gap:.65rem; flex-wrap:wrap; margin-bottom:1.5rem; }
.v-kpi { flex:1; min-width:140px; background:#fff; border:1px solid var(--c-bd); border-radius:12px; padding:.75rem 1rem; border-left:3px solid transparent; }
.v-kpi--g { border-left-color:var(--c-green); } .v-kpi--t { border-left-color:var(--c-teal); }
.v-kpi--a { border-left-color:var(--c-amber); } .v-kpi--r { border-left-color:var(--c-red); }
.v-kpi-lbl { font-size:.6rem; font-weight:800; letter-spacing:.1em; text-transform:uppercase; }
.v-kpi--g .v-kpi-lbl{color:#15803d;} .v-kpi--t .v-kpi-lbl{color:#0369a1;}
.v-kpi--a .v-kpi-lbl{color:#92400e;} .v-kpi--r .v-kpi-lbl{color:#991b1b;}
.v-kpi-val { margin-top:.3rem; font-size:1.3rem; font-weight:800; color:var(--c-txt); letter-spacing:-.02em; }
.v-kpi-sub { font-size:.6rem; color:var(--c-mt); margin-top:.1rem; }

/* Provider selector */
.v-provs { display:flex; gap:.65rem; margin-bottom:1.25rem; }
.v-prov {
    flex:1; background:#fff; border:2px solid var(--c-bd); border-radius:14px;
    padding:.9rem 1rem; cursor:pointer; display:flex; align-items:center; gap:.75rem;
    transition:.15s; position:relative; user-select:none;
}
.v-prov:hover:not(.v-prov--off) { border-color:#9ca3af; }
.v-prov.is-on  { border-color:var(--c-teal); box-shadow:0 0 0 3px rgba(2,132,199,.12); }
.v-prov--off   { opacity:.4; cursor:not-allowed; }
.v-picon { width:44px; height:44px; border-radius:10px; flex-shrink:0; overflow:hidden; display:flex; align-items:center; justify-content:center; }
.v-picon img  { width:100%; height:100%; object-fit:cover; display:block; }
.v-picon--mtn  { background:var(--mtn); }
.v-picon--air  { background:var(--air); }
.v-picon--card { background:#f8fafc; border:1px solid var(--c-bd); }
.v-pname { font-size:.78rem; font-weight:800; color:var(--c-txt); }
.v-psub  { font-size:.62rem; color:var(--c-mt); }
.v-pbadge { position:absolute; top:.55rem; right:.65rem; font-size:.5rem; font-weight:800; letter-spacing:.06em; text-transform:uppercase; padding:.15rem .4rem; border-radius:999px; }
.v-badge--soon { background:#f3f4f6; color:var(--c-mt); }
.v-badge--live { background:var(--c-green-bg); color:#15803d; }
.v-pchk { position:absolute; bottom:.55rem; right:.65rem; width:18px; height:18px; border-radius:50%; background:var(--c-teal); color:#fff; font-size:.55rem; display:none; align-items:center; justify-content:center; }
.v-prov.is-on .v-pchk { display:flex; }

/* Panel */
.v-panel { background:#fff; border:1px solid var(--c-bd); border-radius:16px; overflow:hidden; }
.v-phead { padding:.85rem 1.1rem; border-bottom:1px solid var(--c-bd); display:flex; align-items:center; justify-content:space-between; gap:.75rem; flex-wrap:wrap; }
.v-cbadge { background:var(--c-teal-bg); color:var(--c-teal); border:1px solid var(--c-teal-bd); border-radius:999px; font-size:.63rem; font-weight:800; padding:.2rem .6rem; }
.v-tbadge { background:var(--c-bg2); color:var(--c-txt); border:1px solid var(--c-bd); border-radius:999px; font-size:.63rem; font-weight:800; padding:.2rem .6rem; }
.v-phead-r { display:flex; align-items:center; gap:.5rem; }

/* Buttons */
.v-btn { display:inline-flex; align-items:center; gap:.4rem; padding:.45rem .85rem; border-radius:8px; font-size:.72rem; font-weight:700; font-family:inherit; cursor:pointer; border:1.5px solid; transition:.14s; white-space:nowrap; }
.v-btn--g { background:transparent; border-color:var(--c-bd); color:var(--c-mt); }
.v-btn--g:hover { border-color:#9ca3af; color:var(--c-txt); }
.v-btn--s { background:var(--c-teal); border-color:var(--c-teal); color:#fff; padding:.5rem 1.4rem; font-size:.78rem; }
.v-btn--s:hover:not(:disabled) { background:#0369a1; border-color:#0369a1; }
.v-btn--s:disabled { opacity:.38; cursor:not-allowed; }
.v-btn--s.ld { background:#075985; border-color:#075985; cursor:not-allowed; }
.v-btn--s.ok { background:var(--c-green); border-color:var(--c-green); }
.v-btn--s.er { background:var(--c-red);   border-color:var(--c-red); }

/* Spreadsheet */
.v-scroll { max-height:440px; overflow-y:auto; overflow-x:auto; }
.v-scroll::-webkit-scrollbar{width:4px;height:4px;}
.v-scroll::-webkit-scrollbar-thumb{background:#d1d5db;border-radius:999px;}
.v-tbl { width:100%; border-collapse:collapse; font-size:.75rem; }
.v-tbl thead th { position:sticky; top:0; z-index:2; background:var(--c-bg2); padding:.5rem .6rem; text-align:left; font-size:.58rem; font-weight:800; letter-spacing:.1em; text-transform:uppercase; color:var(--c-mt); border-bottom:1px solid var(--c-bd); white-space:nowrap; }
.v-tbl thead th:first-child{width:38px;text-align:center;}
.v-tbl thead th:last-child{width:36px;}
.v-tbl tbody tr { border-bottom:1px solid #f3f4f6; }
.v-tbl tbody tr:last-child{border-bottom:none;}
.v-tbl tbody tr:hover{background:#fafafa;}
.v-tbl td{padding:.3rem .45rem;vertical-align:middle;}
.v-tbl td:first-child{text-align:center;}
.v-tbl td:last-child{text-align:center;}
.v-rn { display:inline-flex; align-items:center; justify-content:center; width:22px; height:22px; border-radius:50%; background:var(--c-bg2); font-size:.6rem; font-weight:800; color:var(--c-mt); transition:.15s; }
tr.rl .v-rn{background:#dbeafe;color:var(--c-teal);}
tr.ro .v-rn{background:var(--c-green);color:#fff;}
tr.re .v-rn{background:var(--c-red);color:#fff;}
.v-inp { width:100%; border:none; background:transparent; font-size:.75rem; font-family:inherit; color:var(--c-txt); padding:.28rem .4rem; border-radius:5px; outline:none; transition:.12s; }
.v-inp:focus{background:var(--c-teal-bg);box-shadow:0 0 0 1.5px var(--c-teal);}
.v-inp::placeholder{color:#d1d5db;}
.v-inp:disabled{opacity:.4;}
.v-inp--r{text-align:right;font-weight:700;}
.v-rdel { width:26px; height:26px; border-radius:6px; border:none; background:transparent; color:#d1d5db; cursor:pointer; font-size:.68rem; display:inline-flex; align-items:center; justify-content:center; transition:.12s; }
.v-rdel:hover{background:var(--c-red-bg);color:var(--c-red);}

/* Footer */
.v-foot { padding:.75rem 1.1rem; border-top:1px solid var(--c-bd); display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
.v-finfo{font-size:.72rem;color:var(--c-mt);}
.v-finfo strong{color:var(--c-txt);}
.v-foot-r{display:flex;align-items:center;gap:.55rem;}

/* Error bar */
.v-ebar{display:none;align-items:flex-start;gap:.6rem;padding:.7rem 1.1rem;background:var(--c-red-bg);border-top:1px solid var(--c-red-bd);font-size:.72rem;color:#7f1d1d;font-weight:600;}
.v-ebar.on{display:flex;}

/* Modal */
.v-ovl{display:none;position:fixed;inset:0;z-index:300;background:rgba(10,23,16,.6);backdrop-filter:blur(5px);align-items:center;justify-content:center;}
.v-ovl.on{display:flex;}
.v-mdl{background:#fff;border-radius:20px;padding:2.25rem 2rem;max-width:420px;width:90%;text-align:center;animation:min .3s cubic-bezier(.34,1.56,.64,1);}
@keyframes min{from{transform:scale(.8);opacity:0}to{transform:scale(1);opacity:1}}
.v-mico{width:72px;height:72px;margin:0 auto 1.1rem;}
.chk-c{stroke:var(--c-green);stroke-width:3;fill:none;stroke-dasharray:166;stroke-dashoffset:166;animation:dc .55s ease forwards;}
.chk-p{stroke:var(--c-green);stroke-width:3;fill:none;stroke-linecap:round;stroke-linejoin:round;stroke-dasharray:48;stroke-dashoffset:48;animation:dp .35s .5s ease forwards;}
.err-c{stroke:var(--c-red);stroke-width:3;fill:none;stroke-dasharray:166;stroke-dashoffset:166;animation:dc .55s ease forwards;}
.err-l{stroke:var(--c-red);stroke-width:3;stroke-linecap:round;stroke-dasharray:30;stroke-dashoffset:30;}
.err-l1{animation:dp .3s .5s ease forwards;} .err-l2{animation:dp .3s .7s ease forwards;}
@keyframes dc{to{stroke-dashoffset:0}} @keyframes dp{to{stroke-dashoffset:0}}
.v-mttl{font-size:1.15rem;font-weight:800;color:var(--c-txt);}
.v-msub{font-size:.75rem;color:var(--c-mt);margin:.3rem 0 1rem;}
.v-mlns{text-align:left;background:var(--c-bg2);border-radius:10px;padding:.7rem;display:flex;flex-direction:column;gap:.35rem;max-height:210px;overflow-y:auto;margin-bottom:1rem;}
.v-mln{display:flex;align-items:center;gap:.45rem;font-size:.7rem;font-weight:600;}
.v-mln i{width:14px;text-align:center;flex-shrink:0;}
.v-mln--ok i{color:var(--c-green);} .v-mln--ko i{color:var(--c-red);}
.v-mamt{margin-left:auto;font-weight:700;color:var(--c-mt);font-size:.63rem;white-space:nowrap;}
.v-mcls{width:100%;padding:.7rem;border-radius:9px;border:1.5px solid var(--c-bd);background:transparent;color:var(--c-txt);font-family:inherit;font-size:.75rem;font-weight:700;cursor:pointer;transition:.12s;}
.v-mcls:hover{background:var(--c-bg2);}

/* History */
.v-hist{background:#fff;border:1px solid var(--c-bd);border-radius:16px;margin-top:1.25rem;overflow:hidden;}
.v-hd{padding:.85rem 1.1rem;border-bottom:1px solid var(--c-bd);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem;}
.v-ht{font-size:.8rem;font-weight:800;color:var(--c-txt);}
.v-hfs{display:flex;gap:.35rem;}
.v-hf{padding:.3rem .7rem;border-radius:999px;border:1.5px solid var(--c-bd);background:transparent;color:var(--c-mt);font-size:.63rem;font-weight:700;cursor:pointer;transition:.12s;font-family:inherit;}
.v-hf:hover{border-color:#9ca3af;color:var(--c-txt);}
.v-hf.on{background:var(--c-txt);border-color:var(--c-txt);color:#fff;}
.v-htbl{width:100%;border-collapse:collapse;font-size:.72rem;}
.v-htbl thead th{background:var(--c-bg2);padding:.5rem .75rem;text-align:left;font-size:.58rem;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--c-mt);border-bottom:1px solid var(--c-bd);white-space:nowrap;}
.v-htbl tbody tr{border-bottom:1px solid #f3f4f6;}
.v-htbl tbody tr:hover{background:var(--c-bg2);}
.v-htbl tbody tr:last-child{border-bottom:none;}
.v-htbl td{padding:.55rem .75rem;color:#374151;vertical-align:middle;white-space:nowrap;}
.v-typ{display:inline-flex;align-items:center;gap:.3rem;padding:.2rem .5rem;border-radius:999px;font-size:.58rem;font-weight:800;letter-spacing:.05em;text-transform:uppercase;}
.v-typ--i{background:var(--c-green-bg);color:#065f46;border:1px solid var(--c-green-bd);}
.v-typ--o{background:var(--c-teal-bg);color:#075985;border:1px solid var(--c-teal-bd);}
.v-pill{display:inline-flex;align-items:center;gap:.28rem;padding:.2rem .5rem;border-radius:999px;font-size:.62rem;font-weight:700;}
.v-pill::before{content:'';width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0;}
.v-pill--ok{background:var(--c-green-bg);color:#15803d;}
.v-pill--wt{background:var(--c-amber-bg);color:#92400e;}
.v-pill--fl{background:var(--c-red-bg);color:#991b1b;}
.v-pill--uk{background:#f3f4f6;color:#374151;}
.v-mono{font-family:ui-monospace,monospace;font-size:.65rem;color:var(--c-mt);}
.v-ai{font-weight:800;color:var(--c-green);} .v-ao{font-weight:800;color:var(--c-teal);}

@keyframes v-spin{to{transform:rotate(360deg)}}
@keyframes v-shake{0%,100%{transform:translateX(0)}20%,60%{transform:translateX(-6px)}40%,80%{transform:translateX(6px)}}
.v-spin{display:inline-block;animation:v-spin .75s linear infinite;}
.v-shake{animation:v-shake .42s ease;}

/* Mode tabs (Envoyer / Encaisser) */
.v-mode-tabs{display:flex;gap:.35rem;background:#f1f5f9;border:1px solid var(--c-bd);border-radius:10px;padding:.28rem;width:fit-content;margin-bottom:1rem;}
.v-mode-tab{padding:.4rem .9rem;border-radius:7px;font-size:.72rem;font-weight:700;font-family:inherit;cursor:pointer;border:none;background:transparent;color:var(--c-mt);transition:.14s;display:flex;align-items:center;gap:.4rem;}
.v-mode-tab:hover{color:var(--c-txt);}
.v-mode-tab.on{background:#fff;color:var(--c-txt);box-shadow:0 1px 3px rgba(0,0,0,.1);}

/* Collection panel */
.c-panel{background:#fff;border:1px solid var(--c-bd);border-radius:16px;overflow:hidden;display:none;}
.c-panel.on{display:block;}
.c-form{padding:1.25rem 1.1rem;}
.c-fields{display:grid;gap:.85rem;}
.c-field{display:flex;flex-direction:column;gap:.3rem;}
.c-field label{font-size:.6rem;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--c-mt);}
.c-input{border:1.5px solid var(--c-bd);border-radius:9px;padding:.55rem .75rem;font-size:.78rem;font-family:inherit;color:var(--c-txt);background:#fff;transition:.14s;width:100%;}
.c-input:focus{outline:none;border-color:var(--c-teal);box-shadow:0 0 0 3px rgba(2,132,199,.1);}
.c-input::placeholder{color:#d1d5db;}
.c-input:disabled{opacity:.45;}
.c-hint{font-size:.62rem;color:var(--c-mt);margin-top:.15rem;}
.c-foot{padding:.85rem 1.1rem;border-top:1px solid var(--c-bd);display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;}
.c-info{font-size:.72rem;color:var(--c-mt);}
.c-ebar{display:none;align-items:flex-start;gap:.6rem;padding:.7rem 1.1rem;background:var(--c-red-bg);border-top:1px solid var(--c-red-bd);font-size:.72rem;color:#7f1d1d;font-weight:600;}
.c-ebar.on{display:flex;}
/* Collection success card */
.c-ok{display:none;padding:1.5rem 1.1rem;text-align:center;}
.c-ok.on{display:block;}
.c-ok-ico{width:64px;height:64px;margin:0 auto .85rem;}
.c-ok-ttl{font-size:1rem;font-weight:800;color:var(--c-txt);margin-bottom:.3rem;}
.c-ok-sub{font-size:.73rem;color:var(--c-mt);margin-bottom:1rem;}
.c-ok-ref{font-family:ui-monospace,monospace;font-size:.65rem;background:var(--c-bg2);border:1px solid var(--c-bd);border-radius:7px;padding:.4rem .65rem;color:var(--c-mt);display:inline-block;}
.c-ok-btn{margin-top:1rem;padding:.6rem 1.25rem;border-radius:9px;border:1.5px solid var(--c-bd);background:transparent;color:var(--c-txt);font-family:inherit;font-size:.75rem;font-weight:700;cursor:pointer;transition:.12s;}
.c-ok-btn:hover{background:var(--c-bg2);}

#vCsv{display:none;}
@media(max-width:768px){.v-provs{flex-direction:column;}.v-phead,.v-foot,.c-foot{flex-direction:column;align-items:flex-start;}.v-kpis{gap:.45rem;}.v-kpi{min-width:120px;}}
</style>
@endsection

@section('content')

<div class="v-ovl" id="vOvl">
    <div class="v-mdl">
        <div class="v-mico" id="vMico"></div>
        <div class="v-mttl" id="vMttl"></div>
        <div class="v-msub" id="vMsub"></div>
        <div class="v-mlns" id="vMlns"></div>
        <button class="v-mcls" id="vMcls">Nouveau virement</button>
    </div>
</div>

<div class="v-kpis">
    <div class="v-kpi v-kpi--g">
        <div class="v-kpi-lbl"><i class="fa fa-arrow-down-left"></i> Reçu</div>
        <div class="v-kpi-val">{{ number_format($kpis['collected']/100,0,',',' ') }}</div>
        <div class="v-kpi-sub">FCFA encaissé</div>
    </div>
    <div class="v-kpi v-kpi--t">
        <div class="v-kpi-lbl"><i class="fa fa-arrow-up-right"></i> Envoyé</div>
        <div class="v-kpi-val">{{ number_format($kpis['disbursed']/100,0,',',' ') }}</div>
        <div class="v-kpi-sub">FCFA versé</div>
    </div>
    <div class="v-kpi v-kpi--a">
        <div class="v-kpi-lbl"><i class="fa fa-clock"></i> En attente</div>
        <div class="v-kpi-val">{{ $kpis['pending'] }}</div>
        <div class="v-kpi-sub">en cours</div>
    </div>
    <div class="v-kpi v-kpi--r">
        <div class="v-kpi-lbl"><i class="fa fa-xmark"></i> Échecs</div>
        <div class="v-kpi-val">{{ $kpis['failed'] }}</div>
        <div class="v-kpi-sub">à investiguer</div>
    </div>
</div>

<div class="v-provs">
    <div class="v-prov is-on" data-prov="mtn_momo" onclick="selP(this)">
        <div class="v-picon v-picon--mtn">
            <img src="{{ asset('img/logos/mtn.png') }}" alt="MTN MoMo">
        </div>
        <div><div class="v-pname">MTN MoMo</div><div class="v-psub">Mobile money Congo</div></div>
        <span class="v-pbadge v-badge--live">Actif</span>
        <span class="v-pchk"><i class="fa fa-check"></i></span>
    </div>
    <div class="v-prov v-prov--off" data-prov="airtel">
        <div class="v-picon v-picon--air">
            <img src="{{ asset('img/logos/airtel.jpg') }}" alt="Airtel Money">
        </div>
        <div><div class="v-pname">Airtel Money</div><div class="v-psub">Mobile money Congo</div></div>
        <span class="v-pbadge v-badge--soon">Bientôt</span>
        <span class="v-pchk"><i class="fa fa-check"></i></span>
    </div>
    <div class="v-prov v-prov--off" data-prov="card">
        <div class="v-picon v-picon--card">
            <img src="{{ asset('img/logos/cards.avif') }}" alt="Visa / Mastercard" style="object-fit:contain;padding:4px">
        </div>
        <div><div class="v-pname">Carte bancaire</div><div class="v-psub">Visa · Mastercard</div></div>
        <span class="v-pbadge v-badge--soon">Bientôt</span>
        <span class="v-pchk"><i class="fa fa-check"></i></span>
    </div>
</div>

<div class="v-mode-tabs">
    <button class="v-mode-tab on" id="mTabSend" onclick="setMode('send')">
        <i class="fa fa-arrow-up-right"></i> Envoyer
    </button>
    <button class="v-mode-tab" id="mTabColl" onclick="setMode('collect')">
        <i class="fa fa-arrow-down-left"></i> Encaisser
    </button>
</div>

<div class="v-panel" id="vPanel">
    <div class="v-phead">
        <div style="display:flex;align-items:center;gap:.55rem">
            <span class="v-cbadge"><span id="vCnt">0</span> destinataire<span id="vCntS">s</span></span>
            <span class="v-tbadge" id="vTot">0 FCFA</span>
        </div>
        <div class="v-phead-r">
            <label class="v-btn v-btn--g" for="vCsv" style="cursor:pointer">
                <i class="fa fa-upload"></i> Importer CSV
            </label>
            <input type="file" id="vCsv" accept=".csv,.txt">
            <button class="v-btn v-btn--g" onclick="addRow()"><i class="fa fa-plus"></i> Ligne</button>
        </div>
    </div>

    <div class="v-scroll">
        <table class="v-tbl">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nom</th>
                    <th>Numéro</th>
                    <th style="text-align:right;min-width:130px">Montant FCFA</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="vTbody"></tbody>
        </table>
    </div>

    <div class="v-ebar" id="vEbar">
        <i class="fa fa-circle-xmark" style="flex-shrink:0;margin-top:.1rem"></i>
        <div id="vEtxt"></div>
    </div>

    <div class="v-foot">
        <div class="v-finfo" id="vFi">Ajoutez des destinataires.</div>
        <div class="v-foot-r">
            <button class="v-btn v-btn--g" onclick="clearAll()">Vider</button>
            <button class="v-btn v-btn--s" id="vSend" disabled>
                <i class="fa fa-paper-plane" id="vSico"></i>
                <span id="vStxt">Envoyer</span>
            </button>
        </div>
    </div>
</div>

{{-- ========================================================= --}}
{{-- Collection panel (Encaisser) --}}
{{-- ========================================================= --}}
<div class="c-panel" id="cPanel">

    {{-- Form state --}}
    <div id="cForm">
        <div class="c-form">
            <div class="c-fields">
                <div class="c-field">
                    <label for="cPhone"><i class="fa fa-mobile-screen-button"></i> Numéro MTN</label>
                    <input class="c-input" type="tel" id="cPhone" name="cPhone"
                           placeholder="06XXXXXXXX" maxlength="20" autocomplete="tel">
                    <span class="c-hint">Format Congo : 06 ou 05 — ex. 068 234 567</span>
                </div>
                <div class="c-field">
                    <label for="cAmt"><i class="fa fa-coins"></i> Montant (FCFA)</label>
                    <input class="c-input" type="number" id="cAmt" name="cAmt"
                           placeholder="5 000" min="100" max="2000000000" step="100" autocomplete="off">
                </div>
                <div class="c-field">
                    <label for="cDesc"><i class="fa fa-pen-line"></i> Note <span style="font-weight:400;text-transform:none;font-size:.68rem">(optionnel)</span></label>
                    <input class="c-input" type="text" id="cDesc" name="cDesc"
                           placeholder="Régularisation client, remboursement…" maxlength="64" autocomplete="off">
                </div>
            </div>
        </div>

        <div class="c-ebar" id="cEbar">
            <i class="fa fa-circle-xmark" style="flex-shrink:0;margin-top:.1rem"></i>
            <div id="cEtxt"></div>
        </div>

        <div class="c-foot">
            <div class="c-info">
                <i class="fa fa-circle-info"></i>
                Le payeur reçoit une notification USSD sur son téléphone pour approuver.
            </div>
            <button class="v-btn v-btn--s" id="cSend" onclick="sendCollect()">
                <i class="fa fa-bell" id="cSico"></i>
                <span id="cStxt">Demander le paiement</span>
            </button>
        </div>
    </div>

    {{-- Success state --}}
    <div class="c-ok" id="cOk">
        <div class="c-ok-ico">
            <svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="32" cy="32" r="30" stroke="#009543" stroke-width="3"
                    stroke-dasharray="188.5" stroke-dashoffset="188.5"
                    style="animation:dc .5s ease forwards"/>
                <polyline points="18,33 27,43 46,22" stroke="#009543" stroke-width="3"
                    stroke-linecap="round" stroke-linejoin="round"
                    stroke-dasharray="40" stroke-dashoffset="40"
                    style="animation:dp .35s .4s ease forwards"/>
            </svg>
        </div>
        <div class="c-ok-ttl" id="cOkTtl">Demande envoyée</div>
        <div class="c-ok-sub" id="cOkSub">Le payeur va recevoir une notification sur son téléphone.</div>
        <div class="c-ok-ref" id="cOkRef"></div>
        <button class="c-ok-btn" onclick="cReset()">Nouvel encaissement</button>
    </div>

</div>

<div class="v-hist">
    <div class="v-hd">
        <span class="v-ht">Historique</span>
        <div class="v-hfs">
            <button class="v-hf on" data-f="all">Tous</button>
            <button class="v-hf" data-f="collection">Reçus</button>
            <button class="v-hf" data-f="disbursement">Envoyés</button>
        </div>
    </div>
    <div style="overflow-x:auto">
        <table class="v-htbl">
            <thead><tr><th>Flux</th><th>Référence</th><th>Numéro</th><th>Montant</th><th>Statut</th><th>Date</th></tr></thead>
            <tbody>
            @forelse($recent as $tx)
                <tr data-type="{{ $tx->type->value }}">
                    <td><span class="v-typ {{ $tx->type->value==='collection'?'v-typ--i':'v-typ--o' }}">
                        <i class="fa fa-{{ $tx->type->value==='collection'?'arrow-down-left':'arrow-up-right' }}" style="font-size:.5rem"></i>
                        {{ $tx->type->value==='collection'?'Reçu':'Envoyé' }}
                    </span></td>
                    <td><span class="v-mono">{{ Str::limit($tx->external_reference??'—',24) }}</span></td>
                    <td><span class="v-mono">{{ $tx->phone_masked??'—' }}</span></td>
                    <td class="{{ $tx->type->value==='collection'?'v-ai':'v-ao' }}">{{ number_format($tx->amount/100,0,',',' ') }} FCFA</td>
                    <td>
                        @php
                            $sc = match($tx->status->value) {
                                'successful'                    => 'ok',
                                'pending','submitted','created' => 'wt',
                                'failed','cancelled','expired'  => 'fl',
                                default                         => 'uk',
                            };
                        @endphp
                        <span class="v-pill v-pill--{{ $sc }}">{{ ucfirst($tx->status->value) }}</span>
                    </td>
                    <td class="v-mono">{{ $tx->created_at?->format('d/m H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--c-mt);font-size:.75rem">
                    <i class="fa fa-inbox" style="display:block;font-size:1.4rem;opacity:.25;margin-bottom:.4rem"></i>Aucune transaction
                </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function(){
'use strict';

var rows=[],ri=0,busy=false;
var tb=document.getElementById('vTbody');
var cEl=document.getElementById('vCnt'),csEl=document.getElementById('vCntS');
var tEl=document.getElementById('vTot'),fiEl=document.getElementById('vFi');
var sb=document.getElementById('vSend'),si=document.getElementById('vSico'),st=document.getElementById('vStxt');
var eb=document.getElementById('vEbar'),et=document.getElementById('vEtxt');
var ovl=document.getElementById('vOvl');

function fmt(n){return Number(n||0).toLocaleString('fr-FR');}
function esc(s){return String(s==null?'':s).replace(/[&<>"']/g,function(c){return{'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];});}

function reindex(){
    var n=rows.length,tot=rows.reduce(function(s,r){return s+(parseInt(r.amount)||0);},0);
    cEl.textContent=n; csEl.textContent=n===1?'':'s';
    tEl.textContent=fmt(tot)+' FCFA';
    fiEl.innerHTML=n?'<strong>'+n+' destinataire'+(n>1?'s':'')+'</strong> · <strong>'+fmt(tot)+' FCFA</strong>':'Ajoutez des destinataires.';
    sb.disabled=(n===0||busy);
    tb.querySelectorAll('tr').forEach(function(tr,i){
        var nd=tr.querySelector('.v-rn');
        if(nd&&!tr.classList.contains('rl')&&!tr.classList.contains('ro')&&!tr.classList.contains('re'))
            nd.textContent=i+1;
    });
}

window.addRow=function(d){
    var id=ri++,r={id:id,name:'',phone:'',amount:''};
    rows.push(r);
    var tr=document.createElement('tr');
    tr.dataset.rid=id;
    tr.innerHTML='<td><span class="v-rn">'+(rows.length)+'</span></td>'
        +'<td><input class="v-inp" placeholder="Nom" data-f="name"></td>'
        +'<td><input class="v-inp" placeholder="06x xxx xxx" data-f="phone"></td>'
        +'<td><input class="v-inp v-inp--r" placeholder="0" type="number" min="100" data-f="amount"></td>'
        +'<td><button class="v-rdel" onclick="delRow('+id+')" tabindex="-1"><i class="fa fa-xmark"></i></button></td>';
    /* assign values via property (never parsed as HTML) */
    if(d){
        tr.querySelector('[data-f="name"]').value   = d.name   || '';
        tr.querySelector('[data-f="phone"]').value  = d.phone  || '';
        tr.querySelector('[data-f="amount"]').value = d.amount || '';
        r.name=d.name||''; r.phone=d.phone||''; r.amount=d.amount||'';
    }
    tr.querySelectorAll('[data-f]').forEach(function(inp){
        inp.addEventListener('input',function(){r[inp.dataset.f]=inp.value;reindex();});
    });
    tb.appendChild(tr);
    reindex();
    setTimeout(function(){tr.querySelector('[data-f="name"]').focus();},0);
};

window.delRow=function(id){
    rows=rows.filter(function(r){return r.id!==id;});
    var tr=tb.querySelector('[data-rid="'+id+'"]');
    if(tr)tr.remove();
    reindex();
};

window.clearAll=function(){rows=[];tb.innerHTML='';reindex();};

window.selP=function(el){
    if(el.classList.contains('v-prov--off'))return;
    document.querySelectorAll('.v-prov').forEach(function(p){p.classList.remove('is-on');});
    el.classList.add('is-on');
};

/* CSV */
document.getElementById('vCsv').addEventListener('change',function(e){
    var f=e.target.files[0];if(!f)return;
    var r=new FileReader();
    r.onload=function(ev){
        ev.target.result.split(/\r?\n/).filter(Boolean).forEach(function(ln){
            var p=ln.split(/[,;|\t]/);
            if(p.length>=2)addRow({name:(p[0]||'').trim(),phone:(p[1]||'').trim(),amount:(p[2]||'').trim().replace(/\D/g,'')});
        });
    };
    r.readAsText(f);e.target.value='';
});

/* Button states */
function bIdle(){busy=false;sb.disabled=(rows.length===0);sb.className='v-btn v-btn--s';si.className='fa fa-paper-plane';st.textContent='Envoyer';}
function bLoad(){busy=true;sb.disabled=true;sb.className='v-btn v-btn--s ld';si.className='fa fa-rotate v-spin';st.textContent='Traitement…';}
function bOk()  {sb.className='v-btn v-btn--s ok';si.className='fa fa-check';st.textContent='Envoyé';}
function bErr() {sb.className='v-btn v-btn--s er';si.className='fa fa-xmark';st.textContent='Réessayer';setTimeout(bIdle,2200);}

function rSt(id,s){
    var tr=tb.querySelector('[data-rid="'+id+'"]');if(!tr)return;
    tr.className=s?s:'';
    var nd=tr.querySelector('.v-rn');if(!nd)return;
    if(s==='rl')nd.innerHTML='<i class="fa fa-rotate v-spin" style="font-size:.55rem"></i>';
    else if(s==='ro')nd.innerHTML='<i class="fa fa-check" style="font-size:.55rem"></i>';
    else if(s==='re')nd.innerHTML='<i class="fa fa-xmark" style="font-size:.55rem"></i>';
}

sb.addEventListener('click',function(){
    if(busy)return;
    eb.classList.remove('on');
    var valid=rows.filter(function(r){return r.name.trim()&&r.phone.trim()&&parseInt(r.amount)>=100;});
    if(!valid.length){et.textContent='Remplissez au moins un destinataire (nom, numéro, montant ≥ 100 FCFA).';eb.classList.add('on');return;}
    bLoad();
    tb.querySelectorAll('input').forEach(function(i){i.disabled=true;});
    valid.forEach(function(r){rSt(r.id,'rl');});

    fetch('{{ route("admin.gepay.disburse") }}',{
        method:'POST',
        headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,'Accept':'application/json'},
        body:JSON.stringify({recipients:valid.map(function(r){return{name:r.name.trim(),phone:r.phone.trim(),amount:parseInt(r.amount)};})}),
    })
    .then(function(res){return res.json();})
    .then(function(data){
        var results=data.results||[];
        valid.forEach(function(r,i){
            var res=results[i]||{success:false};
            rSt(r.id,res.success?'ro':'re');
        });
        var anyOk=results.some(function(r){return r.success;});
        var allOk=results.every(function(r){return r.success;});
        if(anyOk){bOk();showModal(data.batch_id,results,allOk);}
        else{
            bErr();
            tb.querySelectorAll('input').forEach(function(i){i.disabled=false;});
            var h='';results.filter(function(r){return !r.success;}).forEach(function(r){h+='<div>'+esc(r.name)+' — '+esc(r.message||'Échec')+'</div>';});
            et.innerHTML=h||esc(data.message||'Échec');eb.classList.add('on');
            var p=document.getElementById('vPanel');
            p.classList.remove('v-shake');void p.offsetWidth;p.classList.add('v-shake');
            setTimeout(function(){p.classList.remove('v-shake');},500);
        }
    })
    .catch(function(err){
        bErr();
        tb.querySelectorAll('input').forEach(function(i){i.disabled=false;});
        rows.forEach(function(r){rSt(r.id,null);});
        et.textContent='Erreur réseau : '+err.message;eb.classList.add('on');
    });
});

function showModal(batchId,results,allOk){
    var anyOk=results.some(function(r){return r.success;});
    document.getElementById('vMico').innerHTML=(allOk||anyOk)
        ?'<svg width="72" height="72" viewBox="0 0 52 52"><circle class="chk-c" cx="26" cy="26" r="23"/><path class="chk-p" d="M14 27l7.5 7.5L38 18"/></svg>'
        :'<svg width="72" height="72" viewBox="0 0 52 52"><circle class="err-c" cx="26" cy="26" r="23"/><line class="err-l err-l1" x1="17" y1="17" x2="35" y2="35"/><line class="err-l err-l2" x1="35" y1="17" x2="17" y2="35"/></svg>';
    document.getElementById('vMttl').textContent=allOk?'Virements envoyés':(anyOk?'Partiellement envoyé':'Échec');
    document.getElementById('vMsub').textContent='Lot #'+(batchId||'—')+' · '+results.length+' destinataire'+(results.length>1?'s':'');
    var h='';
    results.forEach(function(r){
        h+='<div class="v-mln v-mln--'+(r.success?'ok':'ko')+'"><i class="fa fa-'+(r.success?'circle-check':'circle-xmark')+'"></i><span>'+esc(r.name)+'</span><span class="v-mamt">'+(r.success&&r.amount?fmt(r.amount/100)+' FCFA':'')+'</span></div>';
        if(!r.success&&r.message)h+='<div style="font-size:.61rem;color:var(--c-red);margin:-.1rem 0 .2rem 1.25rem">'+esc(r.message)+'</div>';
    });
    document.getElementById('vMlns').innerHTML=h;
    ovl.classList.add('on');
}

document.getElementById('vMcls').addEventListener('click',function(){
    ovl.classList.remove('on');
    clearAll();addRow();bIdle();eb.classList.remove('on');
});

document.querySelectorAll('.v-hf').forEach(function(btn){
    btn.addEventListener('click',function(){
        document.querySelectorAll('.v-hf').forEach(function(b){b.classList.remove('on');});
        btn.classList.add('on');
        var f=btn.dataset.f;
        document.querySelectorAll('[data-type]').forEach(function(tr){tr.style.display=(f==='all'||tr.dataset.type===f)?'':'none';});
    });
});

addRow();

/* ── Mode toggle (Envoyer / Encaisser) ───────────────────── */
window.setMode=function(mode){
    var isCollect=(mode==='collect');
    document.getElementById('mTabSend').classList.toggle('on',!isCollect);
    document.getElementById('mTabColl').classList.toggle('on', isCollect);
    document.getElementById('vPanel').style.display=isCollect?'none':'';
    var cp=document.getElementById('cPanel');
    cp.classList.toggle('on',isCollect);
};

/* ── Collection (Encaisser) ──────────────────────────────── */
var cBusy=false;
var csnd=document.getElementById('cSend');
var csic=document.getElementById('cSico');
var cstx=document.getElementById('cStxt');
var ceb =document.getElementById('cEbar');
var cet =document.getElementById('cEtxt');

function cLoad(){
    cBusy=true;csnd.disabled=true;csnd.className='v-btn v-btn--s ld';
    csic.className='fa fa-rotate v-spin';cstx.textContent='Envoi…';
}
function cOkBtn(){
    csnd.className='v-btn v-btn--s ok';csic.className='fa fa-check';cstx.textContent='Envoyé';
}
function cErrBtn(){
    csnd.className='v-btn v-btn--s er';csic.className='fa fa-xmark';cstx.textContent='Réessayer';
    setTimeout(function(){
        cBusy=false;csnd.disabled=false;csnd.className='v-btn v-btn--s';
        csic.className='fa fa-bell';cstx.textContent='Demander le paiement';
    },2200);
}

window.sendCollect=function(){
    if(cBusy)return;
    ceb.classList.remove('on');
    var phone =document.getElementById('cPhone').value.trim();
    var amount=parseInt(document.getElementById('cAmt').value||'0',10);
    var desc  =document.getElementById('cDesc').value.trim();

    if(!phone){cet.textContent='Numéro de téléphone requis.';ceb.classList.add('on');return;}
    if(amount<100){cet.textContent='Montant minimum : 100 FCFA.';ceb.classList.add('on');return;}

    cLoad();
    document.querySelectorAll('#cPanel .c-input').forEach(function(i){i.disabled=true;});

    fetch('{{ route("admin.gepay.collect") }}',{
        method:'POST',
        headers:{
            'Content-Type':'application/json',
            'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,
            'Accept':'application/json',
        },
        body:JSON.stringify({phone:phone,amount:amount,description:desc||undefined}),
    })
    .then(function(res){return res.json();})
    .then(function(data){
        if(data.success){
            cOkBtn();
            document.getElementById('cOkTtl').textContent='Demande envoyée';
            document.getElementById('cOkSub').textContent=esc(data.message||'Le payeur va recevoir une notification sur son téléphone.');
            document.getElementById('cOkRef').textContent=esc(data.ext_ref||data.uuid||'');
            document.getElementById('cForm').style.display='none';
            document.getElementById('cOk').classList.add('on');
        } else {
            cErrBtn();
            document.querySelectorAll('#cPanel .c-input').forEach(function(i){i.disabled=false;});
            cet.textContent=esc(data.message||'Échec de l\'encaissement.');
            ceb.classList.add('on');
            var p=document.getElementById('cPanel');
            p.classList.remove('v-shake');void p.offsetWidth;p.classList.add('v-shake');
            setTimeout(function(){p.classList.remove('v-shake');},500);
        }
    })
    .catch(function(err){
        cErrBtn();
        document.querySelectorAll('#cPanel .c-input').forEach(function(i){i.disabled=false;});
        cet.textContent='Erreur réseau : '+err.message;ceb.classList.add('on');
    });
};

window.cReset=function(){
    cBusy=false;
    document.getElementById('cPhone').value='';
    document.getElementById('cAmt').value='';
    document.getElementById('cDesc').value='';
    document.querySelectorAll('#cPanel .c-input').forEach(function(i){i.disabled=false;});
    document.getElementById('cForm').style.display='';
    document.getElementById('cOk').classList.remove('on');
    ceb.classList.remove('on');
    csnd.disabled=false;csnd.className='v-btn v-btn--s';
    csic.className='fa fa-bell';cstx.textContent='Demander le paiement';
    document.getElementById('cPhone').focus();
};

})();
</script>
@endpush
