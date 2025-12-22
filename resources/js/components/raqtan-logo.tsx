interface RaqtanLogoProps {
    className?: string;
}

export default function RaqtanLogo({ className }: RaqtanLogoProps) {
    return (
        <div className={`flex flex-col items-center ${className || ''}`}>
            <img
                src="/images/raqtan-logo.jpg"
                alt="Raqtan Logo"
                className="h-28 w-auto object-contain"
            />
        </div>
    );
}
