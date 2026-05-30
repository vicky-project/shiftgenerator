<div class="col-4 col-md-2 mb-2">
  <a href="{{ route('shift.web') }}" class="menu-item rounded-4 p-2">
    <i class="bi bi-calendar-range"></i>
    <span>Shift Generator</span>
  </a>
</div>

<style>
  /* ========== GRID MENU KECIL ========== */
  .menu-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    text-decoration: none;
    color: #2d3748;
    background: white;
    border: 1px solid rgba(0,0,0,0.05);
    border-radius: 16px;
    padding: 0.8rem 0.25rem;
    transition: all 0.25s ease;
    min-height: 90px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
  }

  .menu-item i {
    font-size: 1.8rem;
    margin-bottom: 0.3rem;
    color: #667eea;
    transition: color 0.2s, transform 0.2s;
  }

  .menu-item span {
    font-size: 0.8rem;
    font-weight: 500;
    line-height: 1.2;
  }

  .menu-item:hover {
    transform: translateY(-4px);
    box-shadow: 0 15px 25px -8px rgba(102,126,234,0.4);
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-color: transparent;
    }

    .menu-item:hover i {
    color: white;
    }

    /* ===== DARK MODE ===== */
    body[data-bs-theme="dark"] .menu-item {
    background: rgba(255, 255, 255, 0.05);
    border-color: rgba(255, 255, 255, 0.1);
    color: #e9ecef;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    }

    body[data-bs-theme="dark"] .menu-item i {
    color: #a78bfa;
    }

    body[data-bs-theme="dark"] .menu-item:hover {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-color: transparent;
    box-shadow: 0 15px 30px -8px rgba(102,126,234,0.5);
    }

    body[data-bs-theme="dark"] .menu-item:hover i {
    color: white;
    }

    /* ===== RESPONSIVE ===== */
    @media (min-width: 576px) {
    .menu-item {
    padding: 1rem 0.5rem;
    min-height: 100px;
    }
    .menu-item i {
    font-size: 2rem;
    }
    .menu-item span {
    font-size: 0.9rem;
    }
    }

    @media (min-width: 768px) {
    .menu-item {
    padding: 1.2rem 0.5rem;
    }
    .menu-item i {
    font-size: 2.2rem;
    }
    .menu-item span {
    font-size: 1rem;
    }
    }
    </style>