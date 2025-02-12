<x-guest-layout>
    <main class="bg-white">

        <div class="relative flex">

            <!-- Content -->
            <div class="w-full md:w-1/2">

                <div class="min-h-screen h-full flex flex-col after:flex-1">

                    <!-- Header -->
                    <div class="flex-1">
                        <div class="flex items-center justify-between h-16 px-4 sm:px-6 lg:px-8 mt-28">
                            <!-- Logo -->
                            <a href="{{ route('login') }}" class="mx-auto">

                                <img src="{{ Vite::asset('resources/images/logo-2-1.png') }}" alt=""
                                    width="320" height="80" />
                            </a>
                        </div>
                    </div>

                    @if (session('status'))
                        <div class="mb-4 font-medium text-sm text-green-600">
                            {{ session('status') }}
                        </div>
                    @endif
                    <div class="max-w-sm mx-auto px-4 py-8">

                        <h1 class="text-3xl text-slate-800 font-bold mb-6">
                            {{ $empresa->extra['texto_superior_login'] }}! ✨
                        </h1>
                        <!-- Form -->
                        <x-validation-errors class="mb-4" />
                        <form method="POST" action="{{ route('login') }}">
                            @csrf
                            <p tabindex="0"
                                class="focus:outline-none text-2xl font-extrabold leading-6 text-gray-800">
                                Ingresa en tu cuenta</p>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium mb-1" for="email">Correo
                                        Electronico</label>


                                    <x-form.input name="email" id="email" value="{{ old('email') }}"
                                        type="email" required autofocus />
                                </div>
                                <label class="block text-sm font-medium mb-1" for="password">Contraseña</label>
                                <div class="relative flex items-center justify-center">

                                    <x-form.password autocomplete="on" required autocomplete="current-password"
                                        id="password" name="password" />

                                </div>

                            </div>
                            <div class="block mt-4">

                                <x-form.checkbox id="remember_me" name="remember" label="Recuerdame" wire:model="model1"
                                    value="remember_me" />
                            </div>
                            <div class="flex items-center justify-between mt-6">
                                <div class="mr-1">
                                    @if (Route::has('password.request'))
                                        <a class="text-sm underline hover:no-underline"
                                            href="{{ route('password.request') }}">Olvidaste tu
                                            Contraseña?</a>
                                    @endif
                                </div>

                                <x-button type="submit" class="btn bg-indigo-500 hover:bg-indigo-600 text-white ml-3">
                                    INGRESAR
                                </x-button>
                            </div>

                        </form>

                        <!-- Footer -->
                        <div class="pt-5 mt-6 border-t border-slate-200">
                            {{-- <div class="text-sm">
                                No tienes una cuenta? <a class="font-medium text-indigo-500 hover:text-indigo-600"
                                    href="{{ route('register') }}">Registrate</a>
                            </div> --}}
                            <!-- Warning -->
                            <div class="mt-5">
                                <div class="bg-yellow-100 text-yellow-600 px-3 py-2 rounded">
                                    <svg class="inline w-3 h-3 shrink-0 fill-current" viewBox="0 0 12 12">
                                        <path
                                            d="M10.28 1.28L3.989 7.575 1.695 5.28A1 1 0 00.28 6.695l3 3a1 1 0 001.414 0l7-7A1 1 0 0010.28 1.28z" />
                                    </svg>
                                    <span class="text-sm">
                                        {{ $empresa->extra['texto_inferior_login'] }}
                                    </span>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>

            </div>

            <!-- Image -->
            <div class="hidden md:block absolute top-0 bottom-0 right-0 md:w-1/2" aria-hidden="true">
                <img class="object-cover object-center w-full h-full"
                    src="{{ Vite::asset('resources/images/auth-image.jpg') }}" width="760" height="1024"
                    alt="Authentication image" />
                <img class="absolute top-1/4 left-0 transform -translate-x-1/2 ml-8 hidden lg:block"
                    src="{{ Vite::asset('resources/images/auth-decoration-2.png') }}" width="536" height="548"
                    alt="Authentication decoration" />
            </div>


        </div>

    </main>
</x-guest-layout>
