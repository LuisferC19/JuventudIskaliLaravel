@extends('layouts.app')
@section('title', 'Juventud Iskali')
@section('body-class', 'landing-body')
@section('content')
<!-- ══════════════════════════════════════════════════════════════
     🎨 PERSONALIZACIÓN — NAVBAR
     ══════════════════════════════════════════════════════════════ -->
<nav id="navbar">
  <div class="nav-brand">
    <img src="./public/img/Logo.jpeg" alt="Iskalli Logo" style="width:40px;height:40px;border-radius:8px;object-fit:cover;">
    <div>
      <div class="nav-name">Juventud <span>ISKALI</span></div>
    </div>
  </div>
  <ul class="nav-links">
    <li><a href="#inicio">Inicio</a></li>
    <li><a href="#quienes">Quiénes somos</a></li>
    <li><a href="#proyectos">Proyectos</a></li>
    <li><a href="#como-ayudar">Cómo ayudar</a></li>
  </ul>
  <div class="nav-social">
    <a href="https://facebook.com/JuventudIskali" target="_blank" aria-label="Facebook">
      <img src="./public/iconos/logo_facebook.png" alt="Facebook" style="width:20px;height:20px;">
    </a>
    <a href="https://instagram.com/juventud_iskali" target="_blank" aria-label="Instagram">
      <img src="./public/iconos/logo_instagram.png" alt="Instagram" style="width:20px;height:20px;">
    </a>
    <a href="https://tiktok.com/@Juventud.iskali" target="_blank" aria-label="TikTok">
      <img src="./public/iconos/logo_tiktok.png" alt="TikTok" style="width:20px;height:20px;">
    </a>
    <a href="https://youtube.com" target="_blank" aria-label="YouTube">
      <img src="./public/iconos/logo_youtube.png" alt="YouTube" style="width:20px;height:20px;">
    </a>
  </div>
  <div class="nav-actions">
    @auth
      <a href="{{ route('dashboard') }}" class="nav-login btn-icon">
        <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v2m0 14v2M3 12h2m14 0h2M5.6 5.6 7 7m10 10 1.4 1.4m0-12.8L17 7M7 17l-1.4 1.4"/><circle cx="12" cy="12" r="4"/></svg>
        Sistema
      </a>
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="nav-cta btn-icon">
          <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 17l5-5-5-5M15 12H3"/><path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/></svg>
          Salir
        </button>
      </form>
    @else
      <a href="{{ route('login') }}" class="nav-login btn-icon">
        <img src="./public/iconos/Inicio_De_Session.png" alt="" style="width:18px;height:18px;">
        Iniciar sesión
      </a>
    @endauth
    <a href="https://forms.gle/uejAezgSyg4QLCCr8" target="_blank" class="nav-cta btn-icon">
      <img src="./public/iconos/Unete.png" alt="" style="width:18px;height:18px;">
      ¡Únete!
    </a>
  </div>
</nav>
 
<section class="hero" id="inicio">
  <div class="shape shape-1"></div>
  <div class="shape shape-2"></div>
  <div class="shape shape-3"></div>
  <div class="shape shape-4"></div>
 
  <div class="hero-inner">
    <div>
      <div class="hero-badge">
        <div></div><img src="./public/iconos/pin_ubicacion.png" alt="Ser voluntario" style="width:18px;height:18px;">
        Colectivo juvenil · Tlaxcala & Puebla
      </div>
      <h1 class="hero-title">
       Nadie puede hacerlo todo,<br>
        <span class="line-teal">pero todos</span><br>
        <span class="line-magenta">podemos hacer algo.</span>
      </h1>
      <p class="hero-sub">
        Somos un colectivo de jóvenes apasionados por el servicio, la inclusión y la protección ambiental. Desde 2021 llevamos apoyo real a quienes más lo necesitan.
      </p>
      <div class="hero-btns">
        <a href="https://forms.gle/uejAezgSyg4QLCCr8" target="_blank" class="btn-hero-primary btn-icon">
          <img src="./public/iconos/voluntario.png" alt="Ser voluntario" style="width:18px;height:18px;">
          Ser voluntario
        </a>
        <a href="{{ route('login') }}" class="btn-hero-secondary btn-icon">
          <img src="./public/iconos/avatar_usuario.png" alt="Iniciar sesión" style="width:18px;height:18px;">
          Iniciar sesión
        </a>
        <a href="#proyectos" class="btn-hero-secondary btn-icon">
          <img src="./public/iconos/Campanas.png" alt="Ver proyectos" style="width:18px;height:18px;">
          Ver proyectos
        </a>
      </div>
      <div class="hero-stats">
        <div class="stat-item">
          <div class="stat-num">+4</div>
          <div class="stat-lbl">Años activos</div>
        </div>
        <div class="stat-item">
          <div class="stat-num">+10</div>
          <div class="stat-lbl">Proyectos</div>
        </div>
        <div class="stat-item">
          <div class="stat-num">2</div>
          <div class="stat-lbl">Estados</div>
        </div>
        <div class="stat-item">
          <div class="stat-num"><img src="./public/iconos/amor_corazon_manos.png" alt="Con amor" style="width:20px;height:20px;"></div>
          <div class="stat-lbl">Con amor</div>
        </div>
      </div>
    </div>
 
    <div class="hero-mascot">
      <div class="mascot-card">
        <div class="tag-float tag-a"><img src="./public/iconos/sostenible.png" alt="Ser voluntario" style="width:18px;height:18px;"> Sostenible</div>
        <div class="tag-float tag-b"><img src="./public/iconos/inclusion_personas.png" alt="Ser voluntario" style="width:18px;height:18px;">Inclusión</div>
        <div class="tag-float tag-c"><img src="./public/iconos/voluntario.png" alt="Ser voluntario" style="width:18px;height:18px;">Voluntario</div>
        <img src="./public/img/Mascota_Iski_1.jpeg" class="mascot-img" alt="Mascota Iski" style="width:180px;height:180px;border-radius:12px;object-fit:cover;">
        <div class="mascot-name">-ISKI-</div>
        <div class="mascot-desc">Tu amigo de Fundación Iskali A.C.</div>
      </div>
    </div>
  </div>
</section>
 
<div class="wave-divider">
  <svg viewBox="0 0 1440 60" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none" class="wave-svg">
    <path d="M0,30 C360,60 1080,0 1440,30 L1440,60 L0,60 Z" fill="#F5F3FF"/>
  </svg>
</div>
 
<section class="quienes-bg" id="quienes">
  <div class="section-inner">
    <div class="quienes-grid">
      <div class="quienes-text">
        <div class="section-label">¿Quiénes somos?</div>
        <h2 class="section-title">Un colectivo que <span>actúa</span> con el corazón</h2>
        <p class="section-sub">
          Juventud ISKALI es un colectivo juvenil de servicio social enfocado en causas sociales y protección ambiental, operando en Tlaxcala y Puebla. Creemos en acompañar sin invadir, escuchar sin juzgar y actuar con responsabilidad.
        </p>
        <div class="quienes-values">
          <div class="value-card">
          <div class="value-icon"><img src="./public/iconos/servicios_sociales.png" alt="Servicio" style="width:40px;height:40px;"></div>
            <div class="value-title">Servicio</div>
            <div class="value-desc">Acción directa para comunidades vulnerables con respeto y dignidad.</div>
          </div>
          <div class="value-card">
          <div class="value-icon"><img src="./public/iconos/proteccion_naturaleza.png" alt="Ambiente" style="width:40px;height:40px;"></div>
            <div class="value-title">Ambiente</div>
            <div class="value-desc">Reforestación, reciclaje y educación ambiental como ejes centrales.</div>
          </div>
          <div class="value-card">
          <div class="value-icon"><img src="./public/iconos/inclusion_personas.png" alt="Inclusión" style="width:40px;height:40px;"></div>
            <div class="value-title">Inclusión</div>
            <div class="value-desc">Talleres de LSM, braille y apoyo a grupos vulnerados.</div>
          </div>
          <div class="value-card">
          <div class="value-icon"><img src="./public/iconos/liderazgo_manos.png" alt="Liderazgo" style="width:40px;height:40px;"></div>
            <div class="value-title">Liderazgo</div>
            <div class="value-desc">Formamos líderes juveniles conscientes y comprometidos.</div>
          </div>
        </div>
      </div>
 
      <div>
        <!-- ══ TIMELINE CON IMÁGENES (mejorado) ══ -->
        <div class="section-label section-label-large">Nuestra historia</div>
        <div class="timeline-strip">
          <div class="tl-item">
            <div class="tl-year">2021</div>
            <div class="tl-dot"></div>
            <div class="tl-desc">Nace Juventud Iskali</div>
            <div class="tl-img-wrap">
              <img src="./public/img/actividades_2021.png" alt="Actividades 2021">
            </div>
          </div>
          <div class="tl-item">
            <div class="tl-year">2022</div>
            <div class="tl-dot"></div>
            <div class="tl-desc">Donaciones &amp; bazares</div>
            <div class="tl-img-wrap">
              <img src="./public/img/actividades_2022.png" alt="Actividades 2022">
            </div>
          </div>
          <div class="tl-item">
            <div class="tl-year">2023</div>
            <div class="tl-dot"></div>
            <div class="tl-desc">Reforestaciones &amp; Red Club</div>
            <div class="tl-img-wrap">
              <img src="./public/img/actividades_2023.png" alt="Actividades 2023">
            </div>
          </div>
          <div class="tl-item">
            <div class="tl-year">2024</div>
            <div class="tl-dot"></div>
            <div class="tl-desc">Fundación Iskali A.C.</div>
            <div class="tl-img-wrap">
              <img src="./public/img/actividades_2024.png" alt="Actividades 2024">
            </div>
          </div>
          <div class="tl-item">
            <div class="tl-year">2026</div>
            <div class="tl-dot"></div>
            <div class="tl-2025-badge">¡Hoy!</div>
            <div class="tl-desc">Sistema Iskali</div>
            <div class="tl-img-wrap">
              <img src="./public/img/actividades_2025.png" alt="Actividades 2025">
            </div>
          </div>
        </div>

        <div class="section-block">
          <div class="section-label"><img src="./public/iconos/redes_sociales.png" alt="Redes" style="width:18px;height:18px;">  Redes sociales</div>
          <p class="section-sub section-meta">
           <img src="./public/iconos/logo_facebook.png" alt="Facebook" style="width:18px;height:18px;"> <strong> Facebook:</strong> Juventud Iskali · 1.1K seguidores<br>
           <img src="./public/iconos/logo_instagram.png" alt="Instagram" style="width:18px;height:18px;"> <strong> Instagram:</strong> @juventud_iskali · 953 seguidores<br>
           <img src="./public/iconos/Logo_tiktok.png" alt="TikTok" style="width:18px;height:18px;"> <strong> TikTok:</strong> Juventud.iskali
          </p>
        </div>
      </div>
    </div>
  </div>
</section>
 
<section class="proyectos-bg" id="proyectos">
  <div class="section-inner section-center">
    <div class="section-copy">
      <div class="section-label">Proyectos & Actividades</div>
      <h2 class="section-title">Lo que hacemos <span>cada día</span></h2>
      <p class="section-sub">Desde conciertos inclusivos hasta reforestaciones — aquí hay algo para cada voluntario.</p>
    </div>
 
    <div class="proj-grid">
      <div class="proj-card c-teal">
        <div class="proj-cat">Apoyo social</div>
        <div class="proj-icon"><img src="./public/iconos/donaciones.png" alt="Apoyo social" style="width:40px;height:40px;"></div>
        <div class="proj-name">Café para Todos</div>
        <div class="proj-desc">Donaciones de alimentos, despensas, kits escolares, bucales y de mujeres para comunidades vulnerables.</div>
        <div class="proj-tags">
          <span class="proj-tag">Alimentos</span>
          <span class="proj-tag">Kits</span>
          <span class="proj-tag">Comunidad</span>
        </div>
      </div>
 
      <div class="proj-card c-magenta">
        <div class="proj-cat">Inclusión</div>
        <div class="proj-icon"><img src="./public/iconos/inclusion_personas.png" alt="Inclusión" style="width:40px;height:40px;"></div>
        <div class="proj-name">Concierto Inclusivo</div>
        <div class="proj-desc">Arte y música como herramientas de integración. Talleres de braille, Lengua de Señas Mexicana y expresión juvenil.</div>
        <div class="proj-tags">
          <span class="proj-tag">LSM</span>
          <span class="proj-tag">Braille</span>
          <span class="proj-tag">Arte</span>
        </div>
      </div>
 
      <div class="proj-card c-lila">
        <div class="proj-cat">Medio Ambiente</div>
        <div class="proj-icon"><img src="./public/iconos/medio_ambiente_planta.png" alt="Medio Ambiente" style="width:40px;height:40px;"></div>
        <div class="proj-name">Sueños Sostenibles</div>
        <div class="proj-desc">Reforestaciones, limpieza de calles, talleres de huertos, reciclaje, ferias ecológicas y murales conscientes.</div>
        <div class="proj-tags">
          <span class="proj-tag">Reforestación</span>
          <span class="proj-tag">Reciclaje</span>
          <span class="proj-tag">Tapitas</span>
        </div>
      </div>
 
      <div class="proj-card c-teal">
        <div class="proj-cat">Donación responsable</div>
        <div class="proj-icon"><img src="./public/iconos/amor_corazon_manos.png" alt="Re-Vístete" style="width:40px;height:40px;"></div>
        <div class="proj-name">Re-Vístete</div>
        <div class="proj-desc">Manos Amigas, Re-Vístete y donación a centros de rehabilitación. Ropa, libros y mucho más para quienes más lo necesitan.</div>
        <div class="proj-tags">
          <span class="proj-tag">Ropa</span>
          <span class="proj-tag">Libros</span>
          <span class="proj-tag">Rehab</span>
        </div>
      </div>
 
      <div class="proj-card c-magenta">
        <div class="proj-cat">Acompañamiento</div>
        <div class="proj-icon"><img src="./public/iconos/colaboracion_equipo.png" alt="Acompañar" style="width:40px;height:40px;"></div>
        <div class="proj-name">Acompañar sin Juzgar</div>
        <div class="proj-desc">Visitas a hospitales, adultos mayores y centros de rehabilitación. Escucha activa, lenguaje digno y respeto a procesos ajenos.</div>
        <div class="proj-tags">
          <span class="proj-tag">Hospital</span>
          <span class="proj-tag">Adultos mayores</span>
          <span class="proj-tag">Salud</span>
        </div>
      </div>
 
      <div class="proj-card c-lila">
        <div class="proj-cat">Salud mental & Liderazgo</div>
        <div class="proj-icon"><img src="./public/iconos/servicios_sociales.png" alt="Salud mental" style="width:40px;height:40px;"></div>
        <div class="proj-name">Hablemos de Salud Mental</div>
        <div class="proj-desc">Talleres de autoestima, introspección, autocuidado del voluntario y formación de liderazgo juvenil consciente.</div>
        <div class="proj-tags">
          <span class="proj-tag">Autoestima</span>
          <span class="proj-tag">Liderazgo</span>
          <span class="proj-tag">Autocuidado</span>
        </div>
      </div>
    </div>
  </div>
</section>
 
<section id="como-ayudar">
  <div class="section-inner section-center">
    <div class="section-copy">
      <div class="section-label">¿Cómo ayudar?</div>
      <h2 class="section-title">Hay un lugar <span>para ti</span></h2>
      <p class="section-sub">No importa cuánto tiempo tengas — siempre hay una forma de sumar.</p>
    </div>
    <div class="como-grid">
      <div class="como-card">
        <div class="como-num n1">1</div>
        <div class="como-icon"><img src="./public/iconos/voluntarios.png" alt="Ser voluntario" style="width:70px;height:70px;"></div>
        <div class="como-title">Voluntariado</div>
        <div class="como-desc">Únete al equipo y aporta tus manos y conocimiento para llevar a cabo actividades de servicio comunitario.</div>
        <a href="https://forms.gle/uejAezgSyg4QLCCr8" target="_blank" class="como-btn bt1">Quiero ser voluntario</a>
      </div>
      <div class="como-card">
        <div class="como-num n2">2</div>
        <div class="como-icon"><img src="./public/iconos/colaboracion_equipo.png" alt="Colaborador" style="width:70px;height:70px;"></div>
        <div class="como-title">Colaborador</div>
        <div class="como-desc">Si dispones de poco tiempo, pero cuentas con muchas ganas de ayudar y tienes un negocio, puedes ser uno de nuestros centros de acopio o un intermediario en tu comunidad para que podamos ayudar a más personas</div>
        <a href="mailto:contacto@iskali.org" class="como-btn bt2">Ser colaborador</a>
      </div>
      <div class="como-card">
        <div class="como-num n3">3</div>
        <div class="como-icon"><img src="./public/iconos/patrocinador.png" alt="Patrocinador" style="width:70px;height:70px;"></div>
        <div class="como-title">Patrocinador</div>
        <div class="como-desc">Colabora con una cuota monetaria o en especie destinada a casos específicos y campañas activas.</div>
        <a href="mailto:contacto@iskali.org" class="como-btn bt3">Patrocinar</a>
      </div>
    </div>
 
    <!-- Subsección: Tipos de Donador -->
    <div style="margin-top: 4rem;">
      <div class="section-label">¿Cómo quieres participar como donador?</div>
      <p class="section-sub" style="margin-bottom: 2rem;">Elige el tipo de participación que se ajuste mejor a ti y tus capacidades.</p>
      <div class="donor-type-grid">
        <a href="{{ route('donadores.index') }}?tipo=anonimo" class="donor-type-card">
          <div class="donor-type-icon"><img src="./public/iconos/anonimo.png" alt="Anónimo" style="width:70px;height:70px;"></div>
          <div class="donor-type-title">Anónimo (Invitado)</div>
          <div class="donor-type-desc">Dona sin registrarte, mantén tu privacidad</div>
        </a>
        <a href="{{ route('donadores.index') }}?tipo=persona" class="donor-type-card">
          <div class="donor-type-icon"><img src="./public/iconos/persona.png" alt="Persona" style="width:70px;height:70px;"></div>
          <div class="donor-type-title">Persona</div>
          <div class="donor-type-desc">Registro individual con tus datos</div>
        </a>
        <a href="{{ route('donadores.index') }}?tipo=grupo" class="donor-type-card">
          <div class="donor-type-icon"><img src="./public/iconos/grupo.png" alt="Grupo" style="width: 70px;height: 70px;"></div>
          <div class="donor-type-title">Grupo</div>
          <div class="donor-type-desc">Colectivo o equipo de amigos</div>
        </a>
        <a href="{{ route('donadores.index') }}?tipo=organizacion" class="donor-type-card">
          <div class="donor-type-icon"><img src="./public/iconos/empresa.png" alt="Organización" style="width: 70px;height: 70px;"></div>
          <div class="donor-type-title">Organización</div>
          <div class="donor-type-desc">Empresa o institución oficial</div>
        </a>
      </div>
    </div>
  </div>
</section>
 
<!-- ══ COLABORADORES CON FOTOS (mejorado) ══ -->
<section class="colab-bg">
  <div class="section-inner section-center">
    <div class="section-label">Colaboradores</div>
    <h2 class="section-title">Quienes <span>confían</span> en nosotros</h2>
    <div class="colab-grid">

      <div class="colab-card">
        <div class="colab-photo">
          <img src="./public/img/Colaborador3_deRegil.jpeg" alt="de Regil">
        </div>
        <div class="colab-name">de Regil</div>
      </div>

      <div class="colab-card">
        <div class="colab-photo">
          <img src="./public/img/Colaborador1_CreativaColectiva.jpeg" alt="Creativa Colectiva">
        </div>
        <div class="colab-name">Creativa Colectiva</div>
      </div>

      <div class="colab-card">
        <div class="colab-photo">
          <img src="./public/img/Colaborador2_Alebrijes.jpeg" alt="Alebrijes">
        </div>
        <div class="colab-name">Alebrijes</div>
      </div>

      <div class="colab-card">
        <div class="colab-photo">
          <img src="./public/img/Colaborador5_DIVM.jpeg" alt="DIVM Educación Especial">
        </div>
        <div class="colab-name">DIVM Educación Especial</div>
      </div>

      <div class="colab-card">
        <div class="colab-photo">
          <img src="./public/img/Colaborador4_brillantEnglish.jpeg" alt="Brilliant English">
        </div>
        <div class="colab-name">Brilliant English</div>
      </div>

      <div class="colab-card">
        <div class="colab-photo">
          <img src="./public/img/Colaborador9_juventud2000.jpeg" alt="Juventud 2000">
        </div>
        <div class="colab-name">Movimiento Juventud 2000</div>
      </div>

      <div class="colab-card">
        <div class="colab-photo">
          <img src="./public/img/Colaborador7_PizzasNico.jpeg" alt="Pizzas Nico">
        </div>
        <div class="colab-name">Pizzas Nico</div>
      </div>

      <div class="colab-card">
        <div class="colab-photo">
          <img src="./public/img/Colaborador6_fitBody.jpeg" alt="Fit Body">
        </div>
        <div class="colab-name">Fit Body</div>
      </div>

      <div class="colab-card">
        <div class="colab-photo">
          <img src="./public/img/Colaborador8_VentaDProductos.jpeg" alt="Venta de Productos D">
        </div>
        <div class="colab-name">Venta de Productos D</div>
      </div>

      <div class="colab-card">
        <div class="colab-photo">
          <img src="./public/img/Colaborador10_papeleriaNery.jpeg" alt="Papelería Nery Edgar">
        </div>
        <div class="colab-name">Papelería Nery Edgar</div>
      </div>

    </div>
  </div>
</section>
 
<section class="redes-bg" id="redes">
  <div class="section-inner redes-inner">
    <div class="section-label section-label-soft">Síguenos</div>
    <h2 class="redes-title">Encuéntranos en <br>Redes Sociales</h2>
    <p class="redes-sub">Comparte, inspira y suma más manos al cambio.</p>
    <div class="redes-cards">
      <a href="https://facebook.com/JuventudIskali" target="_blank" class="red-card">
        <div class="red-icon"><img src="./public/iconos/logo_facebook.png" alt="Facebook" style="width:40px;height:40px;"></div>
        <div class="red-name">Facebook</div>
        <div class="red-handle">Juventud Iskali</div>
        <div class="red-followers">1,100 seguidores</div>
      </a>
      <a href="https://instagram.com/juventud_iskali" target="_blank" class="red-card">
        <div class="red-icon"><img src="./public/iconos/logo_instagram.png" alt="Instagram" style="width:40px;height:40px;"></div>
        <div class="red-name">Instagram</div>
        <div class="red-handle">@juventud_iskali</div>
        <div class="red-followers">953 seguidores</div>
      </a>
      <a href="https://tiktok.com/@Juventud.iskali" target="_blank" class="red-card">
        <div class="red-icon"><img src="./public/iconos/logo_tiktok.png" alt="TikTok" style="width:40px;height:40px;"></div>
        <div class="red-name">TikTok</div>
        <div class="red-handle">Juventud.iskali</div>
        <div class="red-followers">¡Síguenos!</div>
      </a>
    </div>
 
    <!-- ══ QR MEJORADO ══ -->
    <div class="qr-section">
      <div class="qr-box">
        <div class="qr-label">
          <img src="./public/iconos/formulario_firma.png" alt="Formulario de Voluntario" style="width:16px;height:16px;">
          Formulario de Voluntario
        </div>
        <div class="qr-img">
          <img src="./public/img/Codigo_Informacion.jpeg" alt="Código QR Voluntario">
        </div>
        <div class="qr-badge">
          <span class="qr-badge-dot"></span>
          Escanea para unirte al equipo
        </div>
      </div>
      <div class="qr-copy">
        <p class="qr-title">¿Listo para<br>ser parte del cambio?</p>
        <p class="qr-text">Escanea el código QR o llena el formulario de voluntario. ¡Te esperamos!</p>
        <a href="https://forms.gle/uejAezgSyg4QLCCr8" target="_blank" class="qr-btn btn-icon">
          <img src="./public/iconos/conteo_seguidores.png" alt="Voluntario" style="width:18px;height:18px;"> Llenar formulario
        </a>
      </div>
    </div>
  </div>
</section>
 
<section class="cta-section" id="voluntario">
  <div class="section-inner">
    <div class="cta-pill">¡Únete hoy!</div>
    <h2 class="cta-title">Tu tiempo puede <span class="c-teal">cambiar vidas</span></h2>
    <p class="cta-sub">Cada acción cuenta. Cada voluntario suma. Juntos creamos un impacto real en las comunidades de Tlaxcala y Puebla.</p>
    <a href="https://forms.gle/uejAezgSyg4QLCCr8" target="_blank" class="cta-btn-main btn-icon">
      <img src="./public/iconos/Unete.png" alt="Voluntario" style="width:20px;height:20px;">
      ¡Quiero ser voluntario!
    </a>
    <div class="trust-row">
      <div class="trust-item"><img src="./public/iconos/marca_verificacion.png" alt="Verificado" style="width:18px;height:18px;"> Sin experiencia previa necesaria</div>
      <div class="trust-item"><img src="./public/iconos/marca_verificacion.png" alt="Verificado" style="width:18px;height:18px;"> Tlaxcala & Puebla</div>
      <div class="trust-item"><img src="./public/iconos/marca_verificacion.png" alt="Verificado" style="width:18px;height:18px;"> Comunidad increíble</div>
      <div class="trust-item"><img src="./public/iconos/marca_verificacion.png" alt="Verificado" style="width:18px;height:18px;"> Formación en liderazgo</div>
    </div>
  </div>
</section>
 
<footer>
  <div class="footer-inner">
    <div class="footer-brand">
      <div class="footer-logo">Juventud <span>ISKALI</span></div>
      <div class="footer-meta">Fundación Iskali A.C.</div>
      <p class="footer-tagline">Colectivo juvenil de servicio social enfocado en causas comunitarias y protección ambiental. Tlaxcala & Puebla.</p>
      <p class="footer-contact"> 248 116 6778</p>
    </div>
    <div class="footer-col">
      <h4>Navegación</h4>
      <ul>
        <li><a href="#inicio">Inicio</a></li>
        <li><a href="#quienes">Quiénes somos</a></li>
        <li><a href="#proyectos">Proyectos</a></li>
        <li><a href="#como-ayudar">Cómo ayudar</a></li>
        <li><a href="#redes">Redes Sociales</a></li>
      </ul>
    </div>
    <div class="footer-col">
      <h4>Únete</h4>
      <ul>
        <li><a href="https://forms.gle/uejAezgSyg4QLCCr8" target="_blank">Ser voluntario</a></li>
        <li><a href="mailto:contacto@iskali.org">Ser colaborador</a></li>
        <li><a href="mailto:contacto@iskali.org">Patrocinar</a></li>
        <li><a href="https://facebook.com/JuventudIskali" target="_blank">Facebook</a></li>
        <li><a href="https://instagram.com/juventud_iskali" target="_blank">Instagram</a></li>
      </ul>
    </div>
  </div>
  <div class="footer-bottom">
    <p>© 2026 Fundación Iskali A.C. — Todos los derechos reservados.</p>
    <p class="footer-hearts">Hecho con <span>❤️</span> por y para la comunidad</p>
  </div>
</footer>
 
<script>
const navbar = document.getElementById('navbar');
window.addEventListener('scroll', () => {
  if (!navbar) return;
  if (window.scrollY > 40) navbar.classList.add('scrolled');
  else navbar.classList.remove('scrolled');
});
 
const observer = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.style.opacity = '1';
      entry.target.style.transform = 'translateY(0)';
    }
  });
}, { threshold: 0.1 });
 
document.querySelectorAll('.proj-card, .como-card, .value-card, .colab-card').forEach(el => {
  el.style.opacity = '0';
  el.style.transform = 'translateY(24px)';
  el.style.transition = 'opacity .5s ease, transform .5s ease';
  observer.observe(el);
});
</script>
 

<!-- ══════════════════════════════════════════
     MODALES GLOBALES (disponibles en todas las páginas)
     ══════════════════════════════════════════ -->


<!-- ══════════════════════════════════════════
     TOAST (notificación tipo snackbar)
     ══════════════════════════════════════════ -->
<div id="toast">
  <span>✓</span>
  <span id="toast-msg"></span>
</div>

<!-- JavaScript principal -->
@endsection
