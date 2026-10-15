@extends('layouts.app')
@section('title', 'Planning | Juventud Iskali')
@section('page-title', 'Planning')
@section('content')

<div style="display:flex;width:100%;">
  
  <div class="main">
    @include('partials.topbar')
    
    <div class="content">

      <!-- ══ Encabezado ══════════════════════════════════════════════════════ -->
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.1rem;flex-wrap:wrap;gap:12px;">
        <div>
          <h2 style="font-family:'Syne',sans-serif;font-size:20px;font-weight:700;color:var(--text);margin:0 0 4px;">
            Planning & Actividades
          </h2>
          <p style="font-size:12.5px;color:var(--muted);margin:0;">
            Octubre 2026 — gestión de actividades por zona
          </p>
        </div>
        <button class="btn btn-primary" onclick="abrirModal()">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Nueva actividad
        </button>
      </div>

      <!-- ══ KPI Cards ════════════════════════════════════════════════════════ -->
      <div class="kpi-grid" style="grid-template-columns:repeat(4,1fr);">

        <div class="kpi">
          <div class="kpi-label">Total actividades</div>
          <div class="kpi-value">12</div>
          <div class="kpi-sub">registradas</div>
        </div>

        <div class="kpi amber">
          <div class="kpi-label">Esta semana</div>
          <div class="kpi-value">3</div>
          <div class="kpi-sub">programadas</div>
        </div>

        <div class="kpi blue">
          <div class="kpi-label">San Martín</div>
          <div class="kpi-value">7</div>
          <div class="kpi-sub">actividades</div>
        </div>

        <div class="kpi danger">
          <div class="kpi-label">Tlaxcala</div>
          <div class="kpi-value">5</div>
          <div class="kpi-sub">actividades</div>
        </div>

      </div>

      <!-- ══ Layout: Calendario + Lista ══════════════════════════════════════ -->
      <div style="display:grid;grid-template-columns:1fr 360px;gap:18px;align-items:start;">

        <!-- ─ Calendario ─────────────────────────────────────────────────── -->
        <div class="card" style="overflow:hidden;">

          <!-- Cabecera del calendario -->
          <div class="card-header">
            <div style="display:flex;align-items:center;gap:12px;">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0aafa0" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
              <span style="font-family:'Syne',sans-serif;font-size:15px;font-weight:700;color:#1a2e2c;">
                Octubre 2026              </span>
            </div>
            <div style="display:flex;gap:6px;">
              <a href="{{ route('planning.index', ['mes' => 9, 'anio' => 2026]) }}"
                 style="display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;background:#f0fdfa;border:1px solid #c8efeb;color:#0aafa0;text-decoration:none;font-size:14px;font-weight:700;transition:background .2s;" title="Mes anterior">‹</a>
              <a href="{{ route('planning.index', ['mes' => 10, 'anio' => 2026]) }}"
                 style="display:inline-flex;align-items:center;padding:0 12px;height:32px;border-radius:8px;background:#f0fdfa;border:1px solid #c8efeb;color:#0aafa0;text-decoration:none;font-size:11px;font-weight:700;">Hoy</a>
              <a href="{{ route('planning.index', ['mes' => 11, 'anio' => 2026]) }}"
                 style="display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;background:#f0fdfa;border:1px solid #c8efeb;color:#0aafa0;text-decoration:none;font-size:14px;font-weight:700;">›</a>
            </div>
          </div>

          <!-- Grid de días -->
          <div style="padding:16px 18px;">

            <!-- Encabezados de días -->
            <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:4px;margin-bottom:6px;">
                              <div style="text-align:center;font-size:10.5px;font-weight:700;color:#5a8a84;text-transform:uppercase;letter-spacing:.06em;padding:6px 0;">Lun</div>
                              <div style="text-align:center;font-size:10.5px;font-weight:700;color:#5a8a84;text-transform:uppercase;letter-spacing:.06em;padding:6px 0;">Mar</div>
                              <div style="text-align:center;font-size:10.5px;font-weight:700;color:#5a8a84;text-transform:uppercase;letter-spacing:.06em;padding:6px 0;">Mié</div>
                              <div style="text-align:center;font-size:10.5px;font-weight:700;color:#5a8a84;text-transform:uppercase;letter-spacing:.06em;padding:6px 0;">Jue</div>
                              <div style="text-align:center;font-size:10.5px;font-weight:700;color:#5a8a84;text-transform:uppercase;letter-spacing:.06em;padding:6px 0;">Vie</div>
                              <div style="text-align:center;font-size:10.5px;font-weight:700;color:#5a8a84;text-transform:uppercase;letter-spacing:.06em;padding:6px 0;">Sáb</div>
                              <div style="text-align:center;font-size:10.5px;font-weight:700;color:#5a8a84;text-transform:uppercase;letter-spacing:.06em;padding:6px 0;">Dom</div>
                          </div>

            <!-- Días del mes -->
            <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:4px;">
                              <div style="min-height:72px;border-radius:10px;background:#f8fffe;"></div>
                              <div style="min-height:72px;border-radius:10px;background:#f8fffe;"></div>
                              <div style="min-height:72px;border-radius:10px;background:#f8fffe;"></div>
                              <div style="min-height:72px;border-radius:10px;padding:7px;cursor:pointer;transition:all .15s;border:1px solid #0aafa0;background:#f0fdfa;"
                     onclick="verDia(1)"
                     onmouseover="this.style.background='#e8f9f7';this.style.borderColor='#0aafa0'"
                     onmouseout="this.style.background='#f0fdfa'; this.style.borderColor='#0aafa0'">

                  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                    <span style="font-family:'Syne',sans-serif;font-size:12px;font-weight:800;color:#0aafa0;">1</span>
                                      </div>

                                  </div>
                              <div style="min-height:72px;border-radius:10px;padding:7px;cursor:pointer;transition:all .15s;border:1px solid #f0fdfa;background:#fafffe;"
                     onclick="verDia(2)"
                     onmouseover="this.style.background='#e8f9f7';this.style.borderColor='#0aafa0'"
                     onmouseout="this.style.background='#fafffe'; this.style.borderColor='#f0fdfa'">

                  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                    <span style="font-family:'Syne',sans-serif;font-size:12px;font-weight:600;color:#1a2e2c;">2</span>
                                      </div>

                                  </div>
                              <div style="min-height:72px;border-radius:10px;padding:7px;cursor:pointer;transition:all .15s;border:1px solid #f0fdfa;background:#fafffe;"
                     onclick="verDia(3)"
                     onmouseover="this.style.background='#e8f9f7';this.style.borderColor='#0aafa0'"
                     onmouseout="this.style.background='#fafffe'; this.style.borderColor='#f0fdfa'">

                  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                    <span style="font-family:'Syne',sans-serif;font-size:12px;font-weight:600;color:#1a2e2c;">3</span>
                                      </div>

                                  </div>
                              <div style="min-height:72px;border-radius:10px;padding:7px;cursor:pointer;transition:all .15s;border:1px solid #f0fdfa;background:#fafffe;"
                     onclick="verDia(4)"
                     onmouseover="this.style.background='#e8f9f7';this.style.borderColor='#0aafa0'"
                     onmouseout="this.style.background='#fafffe'; this.style.borderColor='#f0fdfa'">

                  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                    <span style="font-family:'Syne',sans-serif;font-size:12px;font-weight:600;color:#1a2e2c;">4</span>
                                      </div>

                                  </div>
                              <div style="min-height:72px;border-radius:10px;padding:7px;cursor:pointer;transition:all .15s;border:1px solid #c8efeb;background:#f7fffe;"
                     onclick="verDia(5)"
                     onmouseover="this.style.background='#e8f9f7';this.style.borderColor='#0aafa0'"
                     onmouseout="this.style.background='#f7fffe'; this.style.borderColor='#c8efeb'">

                  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                    <span style="font-family:'Syne',sans-serif;font-size:12px;font-weight:600;color:#1a2e2c;">5</span>
                                          <span style="background:#0aafa0;color:#fff;font-size:9px;font-weight:700;border-radius:50%;width:16px;height:16px;display:flex;align-items:center;justify-content:center;">1</span>
                                      </div>

                                                            <div style="background:#E5EEFF;border-left:2px solid var(--blue);border-radius:4px;padding:2px 5px;margin-bottom:2px;overflow:hidden;">
                        <span style="font-size:9px;font-weight:600;color:var(--blue);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block;">
                          Entrega de vív                        </span>
                      </div>
                                                                          </div>
                              <div style="min-height:72px;border-radius:10px;padding:7px;cursor:pointer;transition:all .15s;border:1px solid #f0fdfa;background:#fafffe;"
                     onclick="verDia(6)"
                     onmouseover="this.style.background='#e8f9f7';this.style.borderColor='#0aafa0'"
                     onmouseout="this.style.background='#fafffe'; this.style.borderColor='#f0fdfa'">

                  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                    <span style="font-family:'Syne',sans-serif;font-size:12px;font-weight:600;color:#1a2e2c;">6</span>
                                      </div>

                                  </div>
                              <div style="min-height:72px;border-radius:10px;padding:7px;cursor:pointer;transition:all .15s;border:1px solid #f0fdfa;background:#fafffe;"
                     onclick="verDia(7)"
                     onmouseover="this.style.background='#e8f9f7';this.style.borderColor='#0aafa0'"
                     onmouseout="this.style.background='#fafffe'; this.style.borderColor='#f0fdfa'">

                  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                    <span style="font-family:'Syne',sans-serif;font-size:12px;font-weight:600;color:#1a2e2c;">7</span>
                                      </div>

                                  </div>
                              <div style="min-height:72px;border-radius:10px;padding:7px;cursor:pointer;transition:all .15s;border:1px solid #f0fdfa;background:#fafffe;"
                     onclick="verDia(8)"
                     onmouseover="this.style.background='#e8f9f7';this.style.borderColor='#0aafa0'"
                     onmouseout="this.style.background='#fafffe'; this.style.borderColor='#f0fdfa'">

                  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                    <span style="font-family:'Syne',sans-serif;font-size:12px;font-weight:600;color:#1a2e2c;">8</span>
                                      </div>

                                  </div>
                              <div style="min-height:72px;border-radius:10px;padding:7px;cursor:pointer;transition:all .15s;border:1px solid #f0fdfa;background:#fafffe;"
                     onclick="verDia(9)"
                     onmouseover="this.style.background='#e8f9f7';this.style.borderColor='#0aafa0'"
                     onmouseout="this.style.background='#fafffe'; this.style.borderColor='#f0fdfa'">

                  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                    <span style="font-family:'Syne',sans-serif;font-size:12px;font-weight:600;color:#1a2e2c;">9</span>
                                      </div>

                                  </div>
                              <div style="min-height:72px;border-radius:10px;padding:7px;cursor:pointer;transition:all .15s;border:1px solid #f0fdfa;background:#fafffe;"
                     onclick="verDia(10)"
                     onmouseover="this.style.background='#e8f9f7';this.style.borderColor='#0aafa0'"
                     onmouseout="this.style.background='#fafffe'; this.style.borderColor='#f0fdfa'">

                  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                    <span style="font-family:'Syne',sans-serif;font-size:12px;font-weight:600;color:#1a2e2c;">10</span>
                                      </div>

                                  </div>
                              <div style="min-height:72px;border-radius:10px;padding:7px;cursor:pointer;transition:all .15s;border:1px solid #f0fdfa;background:#fafffe;"
                     onclick="verDia(11)"
                     onmouseover="this.style.background='#e8f9f7';this.style.borderColor='#0aafa0'"
                     onmouseout="this.style.background='#fafffe'; this.style.borderColor='#f0fdfa'">

                  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                    <span style="font-family:'Syne',sans-serif;font-size:12px;font-weight:600;color:#1a2e2c;">11</span>
                                      </div>

                                  </div>
                              <div style="min-height:72px;border-radius:10px;padding:7px;cursor:pointer;transition:all .15s;border:1px solid #c8efeb;background:#f7fffe;"
                     onclick="verDia(12)"
                     onmouseover="this.style.background='#e8f9f7';this.style.borderColor='#0aafa0'"
                     onmouseout="this.style.background='#f7fffe'; this.style.borderColor='#c8efeb'">

                  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                    <span style="font-family:'Syne',sans-serif;font-size:12px;font-weight:600;color:#1a2e2c;">12</span>
                                          <span style="background:#0aafa0;color:#fff;font-size:9px;font-weight:700;border-radius:50%;width:16px;height:16px;display:flex;align-items:center;justify-content:center;">1</span>
                                      </div>

                                                            <div style="background:#E5EEFF;border-left:2px solid var(--blue);border-radius:4px;padding:2px 5px;margin-bottom:2px;overflow:hidden;">
                        <span style="font-size:9px;font-weight:600;color:var(--blue);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block;">
                          Jornada de sal                        </span>
                      </div>
                                                                          </div>
                              <div style="min-height:72px;border-radius:10px;padding:7px;cursor:pointer;transition:all .15s;border:1px solid #f0fdfa;background:#fafffe;"
                     onclick="verDia(13)"
                     onmouseover="this.style.background='#e8f9f7';this.style.borderColor='#0aafa0'"
                     onmouseout="this.style.background='#fafffe'; this.style.borderColor='#f0fdfa'">

                  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                    <span style="font-family:'Syne',sans-serif;font-size:12px;font-weight:600;color:#1a2e2c;">13</span>
                                      </div>

                                  </div>
                              <div style="min-height:72px;border-radius:10px;padding:7px;cursor:pointer;transition:all .15s;border:1px solid #f0fdfa;background:#fafffe;"
                     onclick="verDia(14)"
                     onmouseover="this.style.background='#e8f9f7';this.style.borderColor='#0aafa0'"
                     onmouseout="this.style.background='#fafffe'; this.style.borderColor='#f0fdfa'">

                  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                    <span style="font-family:'Syne',sans-serif;font-size:12px;font-weight:600;color:#1a2e2c;">14</span>
                                      </div>

                                  </div>
                              <div style="min-height:72px;border-radius:10px;padding:7px;cursor:pointer;transition:all .15s;border:1px solid #f0fdfa;background:#fafffe;"
                     onclick="verDia(15)"
                     onmouseover="this.style.background='#e8f9f7';this.style.borderColor='#0aafa0'"
                     onmouseout="this.style.background='#fafffe'; this.style.borderColor='#f0fdfa'">

                  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                    <span style="font-family:'Syne',sans-serif;font-size:12px;font-weight:600;color:#1a2e2c;">15</span>
                                      </div>

                                  </div>
                              <div style="min-height:72px;border-radius:10px;padding:7px;cursor:pointer;transition:all .15s;border:1px solid #f0fdfa;background:#fafffe;"
                     onclick="verDia(16)"
                     onmouseover="this.style.background='#e8f9f7';this.style.borderColor='#0aafa0'"
                     onmouseout="this.style.background='#fafffe'; this.style.borderColor='#f0fdfa'">

                  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                    <span style="font-family:'Syne',sans-serif;font-size:12px;font-weight:600;color:#1a2e2c;">16</span>
                                      </div>

                                  </div>
                              <div style="min-height:72px;border-radius:10px;padding:7px;cursor:pointer;transition:all .15s;border:1px solid #f0fdfa;background:#fafffe;"
                     onclick="verDia(17)"
                     onmouseover="this.style.background='#e8f9f7';this.style.borderColor='#0aafa0'"
                     onmouseout="this.style.background='#fafffe'; this.style.borderColor='#f0fdfa'">

                  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                    <span style="font-family:'Syne',sans-serif;font-size:12px;font-weight:600;color:#1a2e2c;">17</span>
                                      </div>

                                  </div>
                              <div style="min-height:72px;border-radius:10px;padding:7px;cursor:pointer;transition:all .15s;border:1px solid #f0fdfa;background:#fafffe;"
                     onclick="verDia(18)"
                     onmouseover="this.style.background='#e8f9f7';this.style.borderColor='#0aafa0'"
                     onmouseout="this.style.background='#fafffe'; this.style.borderColor='#f0fdfa'">

                  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                    <span style="font-family:'Syne',sans-serif;font-size:12px;font-weight:600;color:#1a2e2c;">18</span>
                                      </div>

                                  </div>
                              <div style="min-height:72px;border-radius:10px;padding:7px;cursor:pointer;transition:all .15s;border:1px solid #f0fdfa;background:#fafffe;"
                     onclick="verDia(19)"
                     onmouseover="this.style.background='#e8f9f7';this.style.borderColor='#0aafa0'"
                     onmouseout="this.style.background='#fafffe'; this.style.borderColor='#f0fdfa'">

                  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                    <span style="font-family:'Syne',sans-serif;font-size:12px;font-weight:600;color:#1a2e2c;">19</span>
                                      </div>

                                  </div>
                              <div style="min-height:72px;border-radius:10px;padding:7px;cursor:pointer;transition:all .15s;border:1px solid #c8efeb;background:#f7fffe;"
                     onclick="verDia(20)"
                     onmouseover="this.style.background='#e8f9f7';this.style.borderColor='#0aafa0'"
                     onmouseout="this.style.background='#f7fffe'; this.style.borderColor='#c8efeb'">

                  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                    <span style="font-family:'Syne',sans-serif;font-size:12px;font-weight:600;color:#1a2e2c;">20</span>
                                          <span style="background:#0aafa0;color:#fff;font-size:9px;font-weight:700;border-radius:50%;width:16px;height:16px;display:flex;align-items:center;justify-content:center;">1</span>
                                      </div>

                                                            <div style="background:#FEF5DC;border-left:2px solid #9A6A00;border-radius:4px;padding:2px 5px;margin-bottom:2px;overflow:hidden;">
                        <span style="font-size:9px;font-weight:600;color:#9A6A00;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block;">
                          Recolecta de r                        </span>
                      </div>
                                                                          </div>
                              <div style="min-height:72px;border-radius:10px;padding:7px;cursor:pointer;transition:all .15s;border:1px solid #f0fdfa;background:#fafffe;"
                     onclick="verDia(21)"
                     onmouseover="this.style.background='#e8f9f7';this.style.borderColor='#0aafa0'"
                     onmouseout="this.style.background='#fafffe'; this.style.borderColor='#f0fdfa'">

                  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                    <span style="font-family:'Syne',sans-serif;font-size:12px;font-weight:600;color:#1a2e2c;">21</span>
                                      </div>

                                  </div>
                              <div style="min-height:72px;border-radius:10px;padding:7px;cursor:pointer;transition:all .15s;border:1px solid #f0fdfa;background:#fafffe;"
                     onclick="verDia(22)"
                     onmouseover="this.style.background='#e8f9f7';this.style.borderColor='#0aafa0'"
                     onmouseout="this.style.background='#fafffe'; this.style.borderColor='#f0fdfa'">

                  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                    <span style="font-family:'Syne',sans-serif;font-size:12px;font-weight:600;color:#1a2e2c;">22</span>
                                      </div>

                                  </div>
                              <div style="min-height:72px;border-radius:10px;padding:7px;cursor:pointer;transition:all .15s;border:1px solid #f0fdfa;background:#fafffe;"
                     onclick="verDia(23)"
                     onmouseover="this.style.background='#e8f9f7';this.style.borderColor='#0aafa0'"
                     onmouseout="this.style.background='#fafffe'; this.style.borderColor='#f0fdfa'">

                  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                    <span style="font-family:'Syne',sans-serif;font-size:12px;font-weight:600;color:#1a2e2c;">23</span>
                                      </div>

                                  </div>
                              <div style="min-height:72px;border-radius:10px;padding:7px;cursor:pointer;transition:all .15s;border:1px solid #f0fdfa;background:#fafffe;"
                     onclick="verDia(24)"
                     onmouseover="this.style.background='#e8f9f7';this.style.borderColor='#0aafa0'"
                     onmouseout="this.style.background='#fafffe'; this.style.borderColor='#f0fdfa'">

                  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                    <span style="font-family:'Syne',sans-serif;font-size:12px;font-weight:600;color:#1a2e2c;">24</span>
                                      </div>

                                  </div>
                              <div style="min-height:72px;border-radius:10px;padding:7px;cursor:pointer;transition:all .15s;border:1px solid #f0fdfa;background:#fafffe;"
                     onclick="verDia(25)"
                     onmouseover="this.style.background='#e8f9f7';this.style.borderColor='#0aafa0'"
                     onmouseout="this.style.background='#fafffe'; this.style.borderColor='#f0fdfa'">

                  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                    <span style="font-family:'Syne',sans-serif;font-size:12px;font-weight:600;color:#1a2e2c;">25</span>
                                      </div>

                                  </div>
                              <div style="min-height:72px;border-radius:10px;padding:7px;cursor:pointer;transition:all .15s;border:1px solid #f0fdfa;background:#fafffe;"
                     onclick="verDia(26)"
                     onmouseover="this.style.background='#e8f9f7';this.style.borderColor='#0aafa0'"
                     onmouseout="this.style.background='#fafffe'; this.style.borderColor='#f0fdfa'">

                  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                    <span style="font-family:'Syne',sans-serif;font-size:12px;font-weight:600;color:#1a2e2c;">26</span>
                                      </div>

                                  </div>
                              <div style="min-height:72px;border-radius:10px;padding:7px;cursor:pointer;transition:all .15s;border:1px solid #f0fdfa;background:#fafffe;"
                     onclick="verDia(27)"
                     onmouseover="this.style.background='#e8f9f7';this.style.borderColor='#0aafa0'"
                     onmouseout="this.style.background='#fafffe'; this.style.borderColor='#f0fdfa'">

                  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                    <span style="font-family:'Syne',sans-serif;font-size:12px;font-weight:600;color:#1a2e2c;">27</span>
                                      </div>

                                  </div>
                              <div style="min-height:72px;border-radius:10px;padding:7px;cursor:pointer;transition:all .15s;border:1px solid #f0fdfa;background:#fafffe;"
                     onclick="verDia(28)"
                     onmouseover="this.style.background='#e8f9f7';this.style.borderColor='#0aafa0'"
                     onmouseout="this.style.background='#fafffe'; this.style.borderColor='#f0fdfa'">

                  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                    <span style="font-family:'Syne',sans-serif;font-size:12px;font-weight:600;color:#1a2e2c;">28</span>
                                      </div>

                                  </div>
                              <div style="min-height:72px;border-radius:10px;padding:7px;cursor:pointer;transition:all .15s;border:1px solid #f0fdfa;background:#fafffe;"
                     onclick="verDia(29)"
                     onmouseover="this.style.background='#e8f9f7';this.style.borderColor='#0aafa0'"
                     onmouseout="this.style.background='#fafffe'; this.style.borderColor='#f0fdfa'">

                  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                    <span style="font-family:'Syne',sans-serif;font-size:12px;font-weight:600;color:#1a2e2c;">29</span>
                                      </div>

                                  </div>
                              <div style="min-height:72px;border-radius:10px;padding:7px;cursor:pointer;transition:all .15s;border:1px solid #f0fdfa;background:#fafffe;"
                     onclick="verDia(30)"
                     onmouseover="this.style.background='#e8f9f7';this.style.borderColor='#0aafa0'"
                     onmouseout="this.style.background='#fafffe'; this.style.borderColor='#f0fdfa'">

                  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                    <span style="font-family:'Syne',sans-serif;font-size:12px;font-weight:600;color:#1a2e2c;">30</span>
                                      </div>

                                  </div>
                              <div style="min-height:72px;border-radius:10px;padding:7px;cursor:pointer;transition:all .15s;border:1px solid #f0fdfa;background:#fafffe;"
                     onclick="verDia(31)"
                     onmouseover="this.style.background='#e8f9f7';this.style.borderColor='#0aafa0'"
                     onmouseout="this.style.background='#fafffe'; this.style.borderColor='#f0fdfa'">

                  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                    <span style="font-family:'Syne',sans-serif;font-size:12px;font-weight:600;color:#1a2e2c;">31</span>
                                      </div>

                                  </div>
                              <div style="min-height:72px;border-radius:10px;background:#f8fffe;"></div>
                          </div>

          </div>

          <!-- Leyenda de estados -->
          <div style="padding:12px 18px 16px;border-top:1px solid #f0fdfa;display:flex;gap:16px;flex-wrap:wrap;">
                          <div style="display:flex;align-items:center;gap:5px;">
                <div style="width:8px;height:8px;border-radius:50%;background:var(--blue);"></div>
                <span style="font-size:11px;color:#5a8a84;">Planeada</span>
              </div>
                          <div style="display:flex;align-items:center;gap:5px;">
                <div style="width:8px;height:8px;border-radius:50%;background:var(--amber);"></div>
                <span style="font-size:11px;color:#5a8a84;">En curso</span>
              </div>
                          <div style="display:flex;align-items:center;gap:5px;">
                <div style="width:8px;height:8px;border-radius:50%;background:var(--accent);"></div>
                <span style="font-size:11px;color:#5a8a84;">Completada</span>
              </div>
                          <div style="display:flex;align-items:center;gap:5px;">
                <div style="width:8px;height:8px;border-radius:50%;background:var(--danger);"></div>
                <span style="font-size:11px;color:#5a8a84;">Cancelada</span>
              </div>
                      </div>
        </div>

        <!-- ─ Panel lateral: Filtros + Lista ────────────────────────────── -->
        <div style="display:flex;flex-direction:column;gap:14px;">

          <!-- Filtro de zona -->
          <div class="card" style="padding:16px;">
            <div class="kpi-label">Filtrar por zona</div>
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                            <a href="{{ route('planning.index', ['mes' => 10, 'anio' => 2026, 'zona' => 'todas']) }}"
                   style="display:inline-flex;align-items:center;padding:5px 12px;border-radius:99px;font-size:11.5px;font-weight:600;text-decoration:none;border:1.5px solid #0aafa0;background:#f0fdfa;color:#0aafa0;transition:all .15s;">
                  Todas                </a>
                              <a href="{{ route('planning.index', ['mes' => 10, 'anio' => 2026, 'zona' => 'san_martin']) }}"
                   style="display:inline-flex;align-items:center;padding:5px 12px;border-radius:99px;font-size:11.5px;font-weight:600;text-decoration:none;border:1.5px solid #e0f2f0;background:#fff;color:#5a8a84;transition:all .15s;">
                  San Martín                </a>
                              <a href="{{ route('planning.index', ['mes' => 10, 'anio' => 2026, 'zona' => 'tlaxcala']) }}"
                   style="display:inline-flex;align-items:center;padding:5px 12px;border-radius:99px;font-size:11.5px;font-weight:600;text-decoration:none;border:1.5px solid #e0f2f0;background:#fff;color:#5a8a84;transition:all .15s;">
                  Tlaxcala                </a>
                              <a href="{{ route('planning.index', ['mes' => 10, 'anio' => 2026, 'zona' => 'ambas']) }}"
                   style="display:inline-flex;align-items:center;padding:5px 12px;border-radius:99px;font-size:11.5px;font-weight:600;text-decoration:none;border:1.5px solid #e0f2f0;background:#fff;color:#5a8a84;transition:all .15s;">
                  Ambas                </a>
                          </div>
          </div>

          <!-- Lista de actividades próximas -->
          <div class="card" style="overflow:hidden;">
            <div class="card-header">
              <h3>Actividades del mes</h3>
              <span class="badge badge-green">3</span>
            </div>

            <div style="max-height:420px;overflow-y:auto;">
                                                <div style="padding:12px 18px;border-bottom:1px solid #f8fffe;transition:background .15s;"
                       onmouseover="this.style.background='#f8fffe'" onmouseout="this.style.background='#fff'">
                    <div style="display:flex;align-items:flex-start;gap:10px;">
                      <div style="width:3px;min-height:40px;border-radius:3px;background:var(--blue);flex-shrink:0;margin-top:2px;"></div>
                      <div style="flex:1;min-width:0;">
                        <div style="font-size:12.5px;font-weight:600;color:#1a2e2c;margin-bottom:4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                          Entrega de víveres                        </div>
                        <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                          <span style="font-size:10px;font-weight:600;color:var(--blue);background:#E5EEFF;padding:2px 7px;border-radius:99px;">Planeada</span>
                          <span style="font-size:10px;font-weight:600;color:var(--blue);background:#E5EEFF;padding:2px 7px;border-radius:99px;">San Martín</span>
                          <span style="font-size:10px;color:#5a8a84;">05/10</span>
                        </div>
                                                  <div style="font-size:10px;color:#5a8a84;margin-top:4px;">
                            👤 Eva Sánchez                          </div>
                                              </div>
                      <!-- Selector de estado inline -->
                      <select onchange="cambiarEstado(1, this.value)"
                              style="font-size:10px;border:1px solid #e0f2f0;border-radius:6px;padding:3px 6px;color:#5a8a84;background:#fff;cursor:pointer;max-width:90px;flex-shrink:0;">
                                                  <option value="planeada" selected>Planeada</option>
                                                  <option value="en_curso" >En curso</option>
                                                  <option value="completada" >Completada</option>
                                                  <option value="cancelada" >Cancelada</option>
                                              </select>
                    </div>
                  </div>
                                  <div style="padding:12px 18px;border-bottom:1px solid #f8fffe;transition:background .15s;"
                       onmouseover="this.style.background='#f8fffe'" onmouseout="this.style.background='#fff'">
                    <div style="display:flex;align-items:flex-start;gap:10px;">
                      <div style="width:3px;min-height:40px;border-radius:3px;background:var(--blue);flex-shrink:0;margin-top:2px;"></div>
                      <div style="flex:1;min-width:0;">
                        <div style="font-size:12.5px;font-weight:600;color:#1a2e2c;margin-bottom:4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                          Jornada de salud                        </div>
                        <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                          <span style="font-size:10px;font-weight:600;color:var(--blue);background:#E5EEFF;padding:2px 7px;border-radius:99px;">Planeada</span>
                          <span style="font-size:10px;font-weight:600;color:var(--pink);background:#FBEAF0;padding:2px 7px;border-radius:99px;">Tlaxcala</span>
                          <span style="font-size:10px;color:#5a8a84;">12/10</span>
                        </div>
                                                  <div style="font-size:10px;color:#5a8a84;margin-top:4px;">
                            👤 Ximena Bautista                          </div>
                                              </div>
                      <!-- Selector de estado inline -->
                      <select onchange="cambiarEstado(2, this.value)"
                              style="font-size:10px;border:1px solid #e0f2f0;border-radius:6px;padding:3px 6px;color:#5a8a84;background:#fff;cursor:pointer;max-width:90px;flex-shrink:0;">
                                                  <option value="planeada" selected>Planeada</option>
                                                  <option value="en_curso" >En curso</option>
                                                  <option value="completada" >Completada</option>
                                                  <option value="cancelada" >Cancelada</option>
                                              </select>
                    </div>
                  </div>
                                  <div style="padding:12px 18px;border-bottom:1px solid #f8fffe;transition:background .15s;"
                       onmouseover="this.style.background='#f8fffe'" onmouseout="this.style.background='#fff'">
                    <div style="display:flex;align-items:flex-start;gap:10px;">
                      <div style="width:3px;min-height:40px;border-radius:3px;background:var(--amber);flex-shrink:0;margin-top:2px;"></div>
                      <div style="flex:1;min-width:0;">
                        <div style="font-size:12.5px;font-weight:600;color:#1a2e2c;margin-bottom:4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                          Recolecta de ropa                        </div>
                        <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                          <span style="font-size:10px;font-weight:600;color:#9A6A00;background:#FEF5DC;padding:2px 7px;border-radius:99px;">En curso</span>
                          <span style="font-size:10px;font-weight:600;color:var(--accent);background:#E4F5ED;padding:2px 7px;border-radius:99px;">Ambas</span>
                          <span style="font-size:10px;color:#5a8a84;">20/10 → 21/10</span>
                        </div>
                                                  <div style="font-size:10px;color:#5a8a84;margin-top:4px;">
                            👤 Eva Sánchez                          </div>
                                              </div>
                      <!-- Selector de estado inline -->
                      <select onchange="cambiarEstado(3, this.value)"
                              style="font-size:10px;border:1px solid #e0f2f0;border-radius:6px;padding:3px 6px;color:#5a8a84;background:#fff;cursor:pointer;max-width:90px;flex-shrink:0;">
                                                  <option value="planeada" >Planeada</option>
                                                  <option value="en_curso" selected>En curso</option>
                                                  <option value="completada" >Completada</option>
                                                  <option value="cancelada" >Cancelada</option>
                                              </select>
                    </div>
                  </div>
                                          </div>
          </div>

        </div><!-- /panel lateral -->
      </div><!-- /grid principal -->

    </div><!-- /content -->
  </div><!-- /main -->
</div>

<!-- ══ Modal: Nueva actividad ══════════════════════════════════════════════ -->
<div id="modal-planning" style="display:none;position:fixed;inset:0;z-index:1000;align-items:center;justify-content:center;">
  <!-- Overlay -->
  <div onclick="cerrarModal()" style="position:absolute;inset:0;background:rgba(10,30,28,.45);backdrop-filter:blur(4px);"></div>

  <!-- Panel -->
  <div style="position:relative;z-index:1001;background:#fff;border-radius:20px;width:90%;max-width:520px;max-height:90vh;overflow-y:auto;box-shadow:0 24px 60px rgba(10,175,160,.18);border:1px solid #c8efeb;">

    <!-- Header del modal -->
    <div style="display:flex;align-items:center;justify-content:space-between;padding:20px 24px;border-bottom:1px solid #f0fdfa;">
      <div style="display:flex;align-items:center;gap:10px;">
        <div style="width:36px;height:36px;border-radius:10px;background:#f0fdfa;display:flex;align-items:center;justify-content:center;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0aafa0" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        </div>
        <div>
          <div style="font-family:'Syne',sans-serif;font-size:15px;font-weight:700;color:#1a2e2c;">Nueva actividad</div>
          <div style="font-size:11px;color:#5a8a84;">Completa los datos de la actividad</div>
        </div>
      </div>
      <button onclick="cerrarModal()" style="width:30px;height:30px;border-radius:8px;border:1px solid #e0f2f0;background:#f8fffe;color:#5a8a84;font-size:18px;cursor:pointer;display:flex;align-items:center;justify-content:center;line-height:1;">×</button>
    </div>

    <!-- Formulario -->
    <form method="POST" action="#">
      @csrf
      <input type="hidden" name="csrf_token" value="mock-token">

      <!-- Título -->
      <div>
        <label style="display:block;font-size:11.5px;font-weight:600;color:#1a2e2c;margin-bottom:6px;">
          Título <span style="color:#ef4444;">*</span>
        </label>
        <input type="text" name="titulo" required maxlength="150"
               placeholder="Ej: Reforestación en parque central"
               style="width:100%;padding:10px 12px;border:1.5px solid #e0f2f0;border-radius:10px;font-size:13px;color:#1a2e2c;background:#fff;outline:none;transition:border .2s;"
               onfocus="this.style.borderColor='#0aafa0'" onblur="this.style.borderColor='#e0f2f0'">
      </div>

      <!-- Descripción -->
      <div>
        <label style="display:block;font-size:11.5px;font-weight:600;color:#1a2e2c;margin-bottom:6px;">Descripción</label>
        <textarea name="descripcion" rows="3" placeholder="Detalles de la actividad..."
                  style="width:100%;padding:10px 12px;border:1.5px solid #e0f2f0;border-radius:10px;font-size:13px;color:#1a2e2c;background:#fff;outline:none;transition:border .2s;resize:vertical;font-family:'DM Sans',sans-serif;"
                  onfocus="this.style.borderColor='#0aafa0'" onblur="this.style.borderColor='#e0f2f0'"></textarea>
      </div>

      <!-- Fechas en grid -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div>
          <label style="display:block;font-size:11.5px;font-weight:600;color:#1a2e2c;margin-bottom:6px;">
            Fecha inicio <span style="color:#ef4444;">*</span>
          </label>
          <input type="date" name="fecha_inicio" id="fecha-inicio" required
                 style="width:100%;padding:10px 12px;border:1.5px solid #e0f2f0;border-radius:10px;font-size:13px;color:#1a2e2c;background:#fff;outline:none;transition:border .2s;"
                 onfocus="this.style.borderColor='#0aafa0'" onblur="this.style.borderColor='#e0f2f0'"
                 onchange="sincronizarFecha(this.value)">
        </div>
        <div>
          <label style="display:block;font-size:11.5px;font-weight:600;color:#1a2e2c;margin-bottom:6px;">Fecha fin</label>
          <input type="date" name="fecha_fin" id="fecha-fin"
                 style="width:100%;padding:10px 12px;border:1.5px solid #e0f2f0;border-radius:10px;font-size:13px;color:#1a2e2c;background:#fff;outline:none;transition:border .2s;"
                 onfocus="this.style.borderColor='#0aafa0'" onblur="this.style.borderColor='#e0f2f0'">
        </div>
      </div>

      <!-- Zona y Estado en grid -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div>
          <label style="display:block;font-size:11.5px;font-weight:600;color:#1a2e2c;margin-bottom:6px;">
            Zona <span style="color:#ef4444;">*</span>
          </label>
          <select name="zona" required
                  style="width:100%;padding:10px 12px;border:1.5px solid #e0f2f0;border-radius:10px;font-size:13px;color:#1a2e2c;background:#fff;outline:none;transition:border .2s;"
                  onfocus="this.style.borderColor='#0aafa0'" onblur="this.style.borderColor='#e0f2f0'">
            <option value="san_martin">San Martín</option>
            <option value="tlaxcala">Tlaxcala</option>
            <option value="ambas" selected>Ambas</option>
          </select>
        </div>
        <div>
          <label style="display:block;font-size:11.5px;font-weight:600;color:#1a2e2c;margin-bottom:6px;">Estado</label>
          <select name="estado"
                  style="width:100%;padding:10px 12px;border:1.5px solid #e0f2f0;border-radius:10px;font-size:13px;color:#1a2e2c;background:#fff;outline:none;transition:border .2s;"
                  onfocus="this.style.borderColor='#0aafa0'" onblur="this.style.borderColor='#e0f2f0'">
            <option value="planeada" selected>Planeada</option>
            <option value="en_curso">En curso</option>
            <option value="completada">Completada</option>
          </select>
        </div>
      </div>

      <!-- Responsable -->
      <div>
        <label style="display:block;font-size:11.5px;font-weight:600;color:#1a2e2c;margin-bottom:6px;">Responsable</label>
        <select name="id_usuario_responsable"
                style="width:100%;padding:10px 12px;border:1.5px solid #e0f2f0;border-radius:10px;font-size:13px;color:#1a2e2c;background:#fff;outline:none;transition:border .2s;"
                onfocus="this.style.borderColor='#0aafa0'" onblur="this.style.borderColor='#e0f2f0'">
          <option value="">— Sin asignar —</option>
                      <option value="1">
              Eva Sánchez            </option>
                      <option value="4">
              Ximena Bautista            </option>
                  </select>
      </div>

      <!-- Botones -->
      <div style="display:flex;gap:10px;margin-top:4px;">
        <button type="submit" id="btn-guardar"
                style="flex:1;padding:11px;background:#0aafa0;color:#fff;border:none;border-radius:12px;font-family:'Syne',sans-serif;font-size:13px;font-weight:700;cursor:pointer;transition:background .2s;display:flex;align-items:center;justify-content:center;gap:6px;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
          Crear actividad
        </button>
        <button type="button" onclick="cerrarModal()"
                style="padding:11px 20px;background:#f8fffe;border:1.5px solid #e0f2f0;border-radius:12px;font-size:13px;font-weight:600;color:#5a8a84;cursor:pointer;transition:all .2s;">
          Cancelar
        </button>
      </div>

    </form>
  </div>
</div>

<!-- ══ Modal: Detalle del día ══════════════════════════════════════════════ -->
<div id="modal-dia" style="display:none;position:fixed;inset:0;z-index:1000;align-items:center;justify-content:center;">
  <div onclick="cerrarModalDia()" style="position:absolute;inset:0;background:rgba(10,30,28,.45);backdrop-filter:blur(4px);"></div>
  <div style="position:relative;z-index:1001;background:#fff;border-radius:20px;width:90%;max-width:440px;max-height:80vh;overflow-y:auto;box-shadow:0 24px 60px rgba(10,175,160,.18);border:1px solid #c8efeb;">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid #f0fdfa;">
      <div style="font-family:'Syne',sans-serif;font-size:15px;font-weight:700;color:#1a2e2c;" id="modal-dia-titulo">Actividades del día</div>
      <button onclick="cerrarModalDia()" style="width:30px;height:30px;border-radius:8px;border:1px solid #e0f2f0;background:#f8fffe;color:#5a8a84;font-size:18px;cursor:pointer;line-height:1;">×</button>
    </div>
    <div id="modal-dia-contenido" style="padding:18px 22px;"></div>
  </div>
</div>

<script>
// ─── Datos del mes para el modal de día ───────────────────────────────────
const actividadesMes = {"5":[{"id_actividad":1,"titulo":"Entrega de víveres","descripcion":"Entrega mensual de víveres","fecha_inicio":"2026-10-05","fecha_fin":"2026-10-05","zona":"san_martin","estado":"planeada","id_responsable":1,"dia":5,"nombre":"Eva","apellido":"Sánchez"}],"12":[{"id_actividad":2,"titulo":"Jornada de salud","descripcion":"Jornada médica gratuita","fecha_inicio":"2026-10-12","fecha_fin":"2026-10-12","zona":"tlaxcala","estado":"planeada","id_responsable":4,"dia":12,"nombre":"Ximena","apellido":"Bautista"}],"20":[{"id_actividad":3,"titulo":"Recolecta de ropa","descripcion":"Recolecta de ropa de invierno","fecha_inicio":"2026-10-20","fecha_fin":"2026-10-21","zona":"ambas","estado":"en_curso","id_responsable":1,"dia":20,"nombre":"Eva","apellido":"Sánchez"}]};
const estadoLabels   = {"planeada":"Planeada","en_curso":"En curso","completada":"Completada","cancelada":"Cancelada"};
const estadoColors   = {"planeada":"var(--blue)","en_curso":"#9A6A00","completada":"#157A45","cancelada":"var(--danger)"};
const estadoBgs      = {"planeada":"#E5EEFF","en_curso":"#FEF5DC","completada":"#E4F5ED","cancelada":"#FDECEA"};
const zonaLabels     = {"san_martin":"San Mart\u00edn","tlaxcala":"Tlaxcala","ambas":"Ambas"};
const mes_actual     = 10;
const anio_actual    = 2026;

// ─── Abrir/cerrar modal de nueva actividad ────────────────────────────────
function abrirModal() {
  const m = document.getElementById('modal-planning');
  m.style.display = 'flex';
  document.body.style.overflow = 'hidden';
}
function cerrarModal() {
  document.getElementById('modal-planning').style.display = 'none';
  document.body.style.overflow = '';
}

// ─── Sincronizar fecha fin con inicio ────────────────────────────────────
function sincronizarFecha(val) {
  const ff = document.getElementById('fecha-fin');
  if (!ff.value) ff.value = val;
  ff.min = val;
}

// ─── Guardar actividad vía AJAX ───────────────────────────────────────────
function guardarActividad(e) {
  e.preventDefault();
  const btn = document.getElementById('btn-guardar');
  btn.disabled = true;
  btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="animation:spin .8s linear infinite"><path d="M21 12a9 9 0 1 1-9-9"/></svg> Guardando...';

  const data = new URLSearchParams(new FormData(document.getElementById('form-actividad')));

  fetch('{{ route('planning.index') }}', {
    method: 'POST',
    body: data
  })
  .then(r => r.json())
  .then(res => {
    if (res.success) {
      showToast('Actividad creada correctamente');
      setTimeout(() => location.reload(), 600);
    } else {
      showToast(res.error || 'Error al crear la actividad');
      btn.disabled = false;
      btn.innerHTML = '✓ Crear actividad';
    }
  })
  .catch(() => {
    showToast('Error de conexión');
    btn.disabled = false;
    btn.innerHTML = '✓ Crear actividad';
  });
}

// ─── Cambiar estado de actividad ─────────────────────────────────────────
function cambiarEstado(id, estado) {
  fetch('{{ route('planning.index') }}', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: 'id=' + id + '&estado=' + estado + '&csrf_token=mock-token'
  })
  .then(r => r.json())
  .then(res => {
    if (res.success) showToast('Estado actualizado');
    else showToast(res.error || 'Error al actualizar');
  })
  .catch(() => showToast('Error de conexión'));
}

// ─── Ver detalle del día ──────────────────────────────────────────────────
function verDia(dia) {
  const acts = actividadesMes[dia];
  if (!acts || !acts.length) return;

  const meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
  document.getElementById('modal-dia-titulo').textContent = dia + ' de ' + meses[mes_actual] + ' ' + anio_actual;

  let html = '';
  acts.forEach(a => {
    const ec = estadoColors[a.estado] || '#6366f1';
    const eb = estadoBgs[a.estado]   || '#eef2ff';
    const el = estadoLabels[a.estado] || a.estado;
    const zl = zonaLabels[a.zona]    || a.zona;
    const fi = a.fecha_inicio ? a.fecha_inicio.substring(0,10) : '';
    const ff = a.fecha_fin    ? a.fecha_fin.substring(0,10)    : '';

    html += `
      <div style="border:1px solid #e0f2f0;border-radius:12px;padding:14px 16px;margin-bottom:10px;border-left:3px solid ${ec};">
        <div style="font-weight:700;font-size:13.5px;color:#1a2e2c;margin-bottom:8px;">${a.titulo}</div>
        <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:6px;">
          <span style="font-size:10.5px;font-weight:600;color:${ec};background:${eb};padding:2px 9px;border-radius:99px;">${el}</span>
          <span style="font-size:10.5px;font-weight:600;color:#5a8a84;background:#f0fdfa;padding:2px 9px;border-radius:99px;">${zl}</span>
        </div>
        ${a.descripcion ? `<div style="font-size:12px;color:#5a8a84;line-height:1.5;margin-bottom:6px;">${a.descripcion}</div>` : ''}
        ${a.nombre ? `<div style="font-size:11px;color:#5a8a84;">👤 ${a.nombre} ${a.apellido}</div>` : ''}
        <div style="font-size:11px;color:#5a8a84;margin-top:4px;">📅 ${fi}${fi!==ff && ff ? ' → '+ff : ''}</div>
      </div>`;
  });

  document.getElementById('modal-dia-contenido').innerHTML = html;
  document.getElementById('modal-dia').style.display = 'flex';
  document.body.style.overflow = 'hidden';
}
function cerrarModalDia() {
  document.getElementById('modal-dia').style.display = 'none';
  document.body.style.overflow = '';
}

// ─── Cerrar modales con Escape ────────────────────────────────────────────
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') { cerrarModal(); cerrarModalDia(); }
});

// ─── Animación spinner para botón ────────────────────────────────────────
const spinStyle = document.createElement('style');
spinStyle.textContent = '@keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}';
document.head.appendChild(spinStyle);
</script>

@endsection
