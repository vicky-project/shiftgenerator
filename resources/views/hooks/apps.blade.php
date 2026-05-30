<div class="col-4 col-md-2 mb-2">
  <a href="{{ route('shift.web') }}" class="menu-item rounded-4 p-2">
    <i class="bi bi-calendar-range"></i>
    <span>Shift Generator</span>
  </a>
</div>

<style>
  /* Grid menu item */
  .menu-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    color: var(--text-muted);
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid var(--border-subtle);
    border-radius: 16px;
    padding: 1rem 0.5rem;
    transition: all 0.3s ease;
    min-height: 90px;
  }

  .menu-item i {
    font-size: 1.8rem;
    margin-bottom: 0.5rem;
    transition: transform 0.3s ease;
  }

  .menu-item span {
    font-size: 0.75rem;
    text-align: center;
    line-height: 1.2;
  }

  .menu-item:hover:not(.disabled) {
    background: rgba(56, 189, 248, 0.1);
    border-color: rgba(56, 189, 248, 0.3);
    color: #fff;
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(56, 189, 248, 0.2);
  }

  .menu-item:hover:not(.disabled) i {
    transform: scale(1.1);
    color: var(--accent-mint);
  }

  .menu-item.disabled {
    opacity: 0.5;
    cursor: not-allowed;
    pointer-events: none;
  }
</style>