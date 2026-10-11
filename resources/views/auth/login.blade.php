<x-layout>
    <div class="bg-white rounded-lg shadow-md w-full md:max-w-xl mx-auto mt-12 p-8 py-12">
        <h2 class="text-4xl text-center font-bold mb-4">Login</h2>
        <form method="POST" action="{{ route('login.authenticate') }}">
            @csrf
            <x-inputs.text id="email" name="email" placeholder="Email address"/>
            <x-inputs.text id="password" name="password" placeholder="Password"/>
            
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded focus:outline-none">Login</button>
            <p class="mt-4 text-gray-500">
                Don't have an account? <a href="{{ route('register') }}" class="text-blue-500 hover:text-blue-600">register</a>
            </p>
        </form>
    </div>
</x-layout>