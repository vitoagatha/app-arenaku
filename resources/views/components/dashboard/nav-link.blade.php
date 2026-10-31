<li><a href="{{ route($route) }}" @if(request()->routeIs($pattern)) aria-current="page" @endif><i class="fas fa-fw fa-{{ $icon }}" aria-hidden="true"></i><span>{{ $label }}</span></a></li>
