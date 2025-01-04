/** @type {import('tailwindcss').Config} */
module.exports = {
	content: ["./includes/**/*.php", "./templates/**/*.php", "./src/js/**/*.js"],

	important: ".booking-plugin", // This is crucial for scoping
	theme: {
		extend: {},
	},
	plugins: [],
	corePlugins: {
		preflight: false,
	},
};
