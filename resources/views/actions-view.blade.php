<div class="relative inline-flex" x-data="{ open: false }">
    <div class="relative inline-block h-full text-left">
        <button class="text-slate-400 hover:text-slate-500 rounded-full"
            :class="{ 'bg-slate-100 dark:bg-slate-900 text-slate-500': open }" aria-haspopup="true"
            @click.prevent="open = !open" :aria-expanded="open">
            <span class="sr-only">Menu</span>
            <svg class="w-8 h-8 fill-current" viewBox="0 0 32 32">
                <circle cx="16" cy="16" r="2" />
                <circle cx="10" cy="16" r="2" />
                <circle cx="22" cy="16" r="2" />
            </svg>
        </button>
        <div class="origin-top-right  z-10 absolute transform  -translate-x-3/4  top-full left-0 min-w-36 bg-white dark:bg-slate-800 border border-slate-200 py-1.5 rounded shadow-lg overflow-hidden mt-1  ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none"
            @click.outside="open = false" @keydown.escape.window="open = false" x-show="open"
            x-transition:enter="transition ease-out duration-200 transform"
            x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-out duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" x-cloak>
            <ul>
                <li>
                    <a href="javascript: void(0)" wire:click.prevent="openModalEdit('{{ $row->id }}')"
                        class="text-slate-800 dark:text-slate-100 group flex items-center px-4 py-2 text-sm font-normal"
                        disabled="false" id="headlessui-menu-item-27" role="menuitem" tabindex="-1">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            class="w-5 h-5 mr-3 text-gray-400 group-hover:text-blue-500">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                            </path>
                        </svg> Editar

                    </a>
                </li>
                <li>
                    <a href="javascript: void(0)" wire:click.prevent="openModalDelete('{{ $row->id }}')"
                        class="text-slate-800 dark:text-slate-100 group flex items-center px-4 py-2 text-sm font-normal"
                        disabled="false" id="headlessui-menu-item-28" role="menuitem" tabindex="-1">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            class="h-5 w-5 mr-3 text-gray-400 group-hover:text-red-500">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                            </path>
                        </svg>
                        Eliminar
                    </a>
                </li>


                @if (in_array('impersonate', $actions))
                    <li>
                        <a href="javascript: void(0)" wire:click.prevent="Impersonate({{ $row }})"
                            class="text-slate-800 dark:text-slate-100 group flex items-center px-4 py-2 text-sm font-normal"
                            disabled="false" id="headlessui-menu-item-28" role="menuitem" tabindex="-1">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 12 12"
                                class="h-5 w-5 mr-3 text-gray-400 group-hover:text-blue-500">
                                <g fill="currentColor" class="nc-icon-wrapper">
                                    <circle cx="6" cy="2.5" r="2" fill="none" stroke="currentColor"
                                        stroke-linecap="round" stroke-linejoin="round" data-color="color-2"></circle>
                                    <path d="M6,6.5a5,5,0,0,0-5,5H11A5,5,0,0,0,6,6.5Z" fill="none"
                                        stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"></path>
                                </g>
                            </svg>
                            Iniciar la sesión como
                        </a>
                    </li>
                @endif
            </ul>
        </div>
    </div>
</div>
